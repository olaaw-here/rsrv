<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\PaymentLog;
use Illuminate\Support\Facades\Log;
use Midtrans\Config as MidtransConfig;
use Midtrans\Snap;
use RuntimeException;
use Illuminate\Support\Str;

class PaymentService
{
    public function __construct()
    {
        MidtransConfig::$serverKey = config('services.midtrans.server_key');
        MidtransConfig::$isProduction = config('services.midtrans.is_production', false);
        MidtransConfig::$isSanitized = true;
        MidtransConfig::$is3ds = true;
    }

    /**
     * Buat transaksi Snap di Midtrans untuk sebuah booking yang statusnya
     * masih 'pending_payment', lalu simpan record Payment.
     */
    public function initiateForBooking(Booking $booking): Payment
    {
        $booking->loadMissing(['user', 'resource', 'bookingSlots.timeSlot']);

        $existing = $booking->payments()
            ->where('status', 'pending')
            ->where(function ($query) {
                $query->whereNull('expired_at')->orWhere('expired_at', '>', now());
            })
            ->latest()
            ->first();

        if ($existing) {
            return $existing;
        }

        $params = [
            'transaction_details' => [
                'order_id'     => $this->generateOrderId($booking),
                'gross_amount' => (int) $booking->total_price,
            ],
            'customer_details' => [
                'first_name' => $booking->user->name,
                'email'      => $booking->user->email,
                'phone'      => $booking->user->phone,
            ],
            'item_details' => $booking->bookingSlots->map(fn ($bs) => [
                'id'       => 'SLOT-' . $bs->time_slot_id,
                'price'    => (int) $bs->price_snapshot,
                'quantity' => 1,
                'name'     => $booking->resource->name . ' - ' . $bs->timeSlot->slot_date->format('d M Y') . ' ' . $bs->timeSlot->start_time,
            ])->toArray(),
            // Snap Token kedaluwarsa mengikuti waktu hold booking,
            'expiry' => [
                'start_time' => now()->format('Y-m-d H:i:s O'),
                'unit' => 'minute',
                'duration' => max(1, now()->diffInMinutes($booking->expires_at)),
            ],
        ];

        $snapToken = Snap::getSnapToken($params);

        return Payment::create([
            'booking_id'     => $booking->id,
            'gateway'        => 'midtrans',
            'transaction_id' => $params['transaction_details']['order_id'],
            'amount'         => $booking->total_price,
            'status'         => 'pending',
            'snap_token'     => $snapToken,
            'payment_url'    => null, 
            'expired_at'     => $booking->expires_at,
        ]);
    }

    /**
     * order_id harus unik di Midtrans. Gunakan booking_code + suffix acak
     * pendek supaya request ulang (mis. re-initiate payment) tidak bentrok
     * dengan order_id transaksi sebelumnya yang sudah expired/cancel.
     */
    protected function generateOrderId(Booking $booking): string
    {
        return $booking->booking_code . '-' . now()->timestamp . '-' . Str::upper(Str::random(6));
    }

    /**
     * Verifikasi signature_key dari payload notifikasi Midtrans.
     * Formula resmi: SHA512(order_id + status_code + gross_amount + ServerKey)
     */
    public function verifySignature(array $payload): bool
    {
        $orderId = $payload['order_id'] ?? '';
        $statusCode = $payload['status_code'] ?? '';
        $grossAmount = $payload['gross_amount'] ?? '';
        $signatureKey = $payload['signature_key'] ?? '';

        $expected = hash('sha512', $orderId . $statusCode . $grossAmount . config('services.midtrans.server_key'));

        return hash_equals($expected, $signatureKey);
    }

    /**
     * Proses notifikasi webhook dari Midtrans. Dipanggil dari
     * PaymentWebhookController::handleMidtrans() SETELAH signature valid.
     *
     * Idempotent: jika notification_id sudah pernah dicatat, tidak ada
     * perubahan status yang dilakukan lagi.
     */
    public function handleNotification(array $payload): void
    {
        $orderId = $payload['order_id'] ?? null;
        $notificationId = $this->notificationKey($payload);
        $transactionStatus = $payload['transaction_status'] ?? null;
        $fraudStatus = $payload['fraud_status'] ?? null;

        $payment = Payment::where('transaction_id', $orderId)->first();

        if (! $payment) {
            Log::warning('Webhook Midtrans: payment tidak ditemukan', ['order_id' => $orderId]);
            return;
        }

        // --- Idempotency check ---
        if ($payment->hasProcessedNotification($notificationId)) {
            Log::info('Webhook Midtrans: notifikasi duplikat, dilewati', [
                'notification_id' => $notificationId,
            ]);
            return;
        }

        PaymentLog::create([
            'payment_id'      => $payment->id,
            'event_type'      => $transactionStatus ?? 'unknown',
            'notification_id' => $notificationId,
            'raw_payload'     => $payload,
            'signature_valid' => true,
            'received_at'     => now(),
        ]);

        $booking = $payment->booking;

        match (true) {
            in_array($transactionStatus, ['capture', 'settlement']) && $fraudStatus !== 'deny'
                => $this->markAsPaid($payment, $booking),

            in_array($transactionStatus, ['expire', 'cancel', 'deny'])
                => $this->markAsFailed($payment, $booking, $transactionStatus),

            $transactionStatus === 'pending'
                => null, // tidak ada perubahan, tunggu notifikasi berikutnya

            default => Log::warning('Webhook Midtrans: status tidak dikenali', [
                'transaction_status' => $transactionStatus,
            ]),
        };
    }

    protected function notificationKey(array $payload): string
    {
        ksort($payload);
        return hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    protected function markAsPaid(Payment $payment, Booking $booking): void
    {
        $payment->update(['status' => 'settlement', 'paid_at' => now()]);

        // confirm() sudah membungkus DB::transaction() -> update booking
        // jadi 'confirmed' dan slot terkait jadi 'booked'. Lihat app/Models/Booking.php.
        $booking->confirm();
    }

    protected function markAsFailed(Payment $payment, Booking $booking, string $transactionStatus): void
    {
        $payment->update(['status' => $transactionStatus]);

        // releaseSlots() melepas slot kembali ke 'available' agar bisa
        // dipesan orang lain, dan set status booking sesuai transactionStatus.
        $booking->releaseSlots($transactionStatus === 'expire' ? 'expired' : 'cancelled');
    }
}
