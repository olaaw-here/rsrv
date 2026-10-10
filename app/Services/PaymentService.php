<?php

namespace App\Services;

use App\Exceptions\PaymentGatewayException;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\PaymentLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Midtrans\Config as MidtransConfig;
use Midtrans\Snap;
use Midtrans\Transaction;
use Throwable;

class PaymentService
{
    public function __construct()
    {
        MidtransConfig::$serverKey = config('services.midtrans.server_key');
        MidtransConfig::$isProduction = filter_var(
            config('services.midtrans.is_production', false),
            FILTER_VALIDATE_BOOLEAN
        );
        MidtransConfig::$isSanitized = true;
        MidtransConfig::$is3ds = true;

        // Cegah request Midtrans yang menggantung memblokir worker PHP.
        // CURLOPT_HTTPHEADER tetap didefinisikan karena SDK membacanya saat
        // menggabungkan opsi cURL default.
        MidtransConfig::$curlOptions = array_replace(
            MidtransConfig::$curlOptions ?? [],
            [
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_HTTPHEADER => [],
            ]
        );
    }

    /**
     * Buat transaksi Snap di Midtrans untuk sebuah booking yang statusnya
     * masih 'pending_payment', lalu simpan record Payment.
     *
     * @throws PaymentGatewayException jika gateway belum dikonfigurasi / gagal
     * @throws \RuntimeException       jika booking tidak boleh dibayar lagi
     */
    public function initiateForBooking(Booking $booking): Payment
    {
        if ($booking->status !== 'pending_payment') {
            throw new \RuntimeException('Booking tidak dalam status menunggu pembayaran.');
        }

        if ($booking->expires_at && $booking->expires_at->lte(now())) {
            throw new \RuntimeException('Waktu pembayaran booking sudah habis.');
        }

        if (empty(config('services.midtrans.server_key'))) {
            throw new PaymentGatewayException(
                'Midtrans belum dikonfigurasi. Isi MIDTRANS_SERVER_KEY di file .env terlebih dahulu.'
            );
        }

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

        $items = $booking->bookingSlots->map(fn ($bs) => [
            'id'       => 'SLOT-' . $bs->time_slot_id,
            'price'    => (int) round((float) $bs->price_snapshot),
            'quantity' => 1,
            'name'     => $booking->resource->name . ' - ' . $bs->timeSlot->slot_date->format('d M Y') . ' ' . $bs->timeSlot->start_time,
        ])->values()->all();

        // Midtrans mewajibkan jumlah item_details == gross_amount, jadi
        // gross_amount dihitung dari item yang sama (bukan dari total_price).
        $grossAmount = array_sum(array_column($items, 'price'));

        $minutes = $booking->expires_at
            ? max(1, (int) floor(now()->diffInMinutes($booking->expires_at, false)))
            : 15;

        $params = [
            'transaction_details' => [
                'order_id'     => $this->generateOrderId($booking),
                'gross_amount' => $grossAmount,
            ],
            'customer_details' => [
                'first_name' => $booking->user->name,
                'email'      => $booking->user->email,
                'phone'      => $booking->user->phone,
            ],
            'item_details' => $items,
            // Snap Token kedaluwarsa mengikuti waktu hold booking.
            'expiry' => [
                'start_time' => now()->format('Y-m-d H:i:s O'),
                'unit'       => 'minute',
                'duration'   => $minutes,
            ],
        ];

        try {
            $snapToken = Snap::getSnapToken($params);
        } catch (Throwable $e) {
            throw new PaymentGatewayException('Gagal membuat transaksi di Midtrans: ' . $e->getMessage(), 0, $e);
        }

        return Payment::create([
            'booking_id'     => $booking->id,
            'gateway'        => 'midtrans',
            'transaction_id' => $params['transaction_details']['order_id'],
            'amount'         => $grossAmount,
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
     * Batalkan transaksi di sisi Midtrans (best effort) supaya customer tidak
     * bisa lagi membayar booking yang sudah dibatalkan. Kegagalan hanya
     * dicatat: pembatalan lokal tetap sah, dan webhook yang terlambat
     * ditangani oleh handleNotification().
     */
    public function cancelGatewayTransaction(Payment $payment): void
    {
        if (empty(config('services.midtrans.server_key'))) {
            return;
        }

        try {
            Transaction::cancel($payment->transaction_id);
        } catch (Throwable $e) {
            Log::info('Midtrans cancel dilewati/gagal', [
                'order_id' => $payment->transaction_id,
                'error'    => $e->getMessage(),
            ]);
        }
    }

    /**
     * Verifikasi signature_key dari payload notifikasi Midtrans.
     * Formula resmi: SHA512(order_id + status_code + gross_amount + ServerKey)
     *
     * Jika server key belum diisi, semua notifikasi DITOLAK. Tanpa ini
     * siapa pun bisa menghitung signature yang valid (hash dengan kunci
     * kosong) dan menandai booking sebagai lunas.
     */
    public function verifySignature(array $payload): bool
    {
        $serverKey = (string) config('services.midtrans.server_key');

        if ($serverKey === '') {
            return false;
        }

        $orderId = (string) ($payload['order_id'] ?? '');
        $statusCode = (string) ($payload['status_code'] ?? '');
        $grossAmount = (string) ($payload['gross_amount'] ?? '');
        $signatureKey = (string) ($payload['signature_key'] ?? '');

        if ($orderId === '' || $signatureKey === '') {
            return false;
        }

        $expected = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        return hash_equals($expected, $signatureKey);
    }

    /**
     * Proses notifikasi webhook dari Midtrans. Dipanggil dari
     * PaymentWebhookController::handleMidtrans() SETELAH signature valid.
     *
     * - Idempotent: payload identik yang sudah dicatat tidak diproses lagi.
     * - Seluruh proses berjalan dalam satu transaksi dengan row lock pada
     *   payment & booking, sehingga dua webhook bersamaan tidak saling
     *   menimpa.
     * - Status pembayaran tidak pernah mundur (mis. 'expire' yang datang
     *   terlambat tidak membatalkan pembayaran yang sudah settlement).
     */
    public function handleNotification(array $payload): void
    {
        $orderId = $payload['order_id'] ?? null;
        $notificationId = $this->notificationKey($payload);
        $transactionStatus = $payload['transaction_status'] ?? null;
        $fraudStatus = $payload['fraud_status'] ?? null;

        DB::transaction(function () use ($payload, $orderId, $notificationId, $transactionStatus, $fraudStatus) {
            $payment = Payment::where('transaction_id', $orderId)->lockForUpdate()->first();

            if (! $payment) {
                Log::warning('Webhook Midtrans: payment tidak ditemukan', ['order_id' => $orderId]);
                return;
            }

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

            $booking = Booking::whereKey($payment->booking_id)->lockForUpdate()->firstOrFail();

            $isPaidStatus = in_array($transactionStatus, ['capture', 'settlement'], true);

            match (true) {
                $isPaidStatus && $fraudStatus === 'deny'
                    => $this->markAsFailed($payment, $booking, 'deny'),

                // 'challenge' = menunggu review manual fraud di dashboard Midtrans.
                $isPaidStatus && $fraudStatus === 'challenge'
                    => Log::warning('Webhook Midtrans: transaksi challenge, menunggu review', ['order_id' => $orderId]),

                $isPaidStatus
                    => $this->markAsPaid($payment, $booking, $payload),

                in_array($transactionStatus, ['expire', 'cancel', 'deny'], true)
                    => $this->markAsFailed($payment, $booking, $transactionStatus),

                $transactionStatus === 'pending'
                    => null, // tidak ada perubahan, tunggu notifikasi berikutnya

                default => Log::warning('Webhook Midtrans: status tidak dikenali', [
                    'transaction_status' => $transactionStatus,
                ]),
            };
        });
    }

    protected function notificationKey(array $payload): string
    {
        ksort($payload);
        return hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    protected function markAsPaid(Payment $payment, Booking $booking, array $payload = []): void
    {
        // Sudah lunas / sudah direfund: abaikan notifikasi susulan.
        if (in_array($payment->status, ['settlement', 'refunded'], true)) {
            return;
        }

        // Nominal yang dibayar harus sama dengan nominal tagihan.
        if (isset($payload['gross_amount'])
            && (int) round((float) $payload['gross_amount']) !== (int) round((float) $payment->amount)) {
            Log::critical('Webhook Midtrans: nominal tidak cocok dengan tagihan', [
                'order_id' => $payment->transaction_id,
                'expected' => $payment->amount,
                'received' => $payload['gross_amount'],
            ]);
            return;
        }

        $payment->update([
            'status'         => 'settlement',
            'paid_at'        => now(),
            'payment_method' => $payload['payment_type'] ?? $payment->payment_method,
        ]);

        if ($booking->status === 'pending_payment') {
            try {
                $booking->confirm();
                return;
            } catch (\RuntimeException $e) {
                Log::critical('Webhook Midtrans: pembayaran masuk tetapi slot tidak bisa dikonfirmasi', [
                    'order_id' => $payment->transaction_id,
                    'booking'  => $booking->booking_code,
                    'error'    => $e->getMessage(),
                ]);
                $this->flagForRefund($payment, $booking);
                return;
            }
        }

        if (in_array($booking->status, ['confirmed', 'completed'], true)) {
            return; // sudah terkonfirmasi (mis. manual oleh provider)
        }

        // Booking sudah expired/cancelled tetapi uang masuk: coba ambil slot lagi.
        if ($booking->reclaimSlots()) {
            Log::info('Webhook Midtrans: pembayaran terlambat, slot berhasil diambil kembali', [
                'booking' => $booking->booking_code,
            ]);
            return;
        }

        $this->flagForRefund($payment, $booking);
    }

    protected function markAsFailed(Payment $payment, Booking $booking, string $transactionStatus): void
    {
        // Jangan pernah menurunkan pembayaran yang sudah settlement/refunded/gagal.
        if ($payment->status !== 'pending') {
            return;
        }

        $payment->update(['status' => $transactionStatus]);

        if ($booking->status !== 'pending_payment') {
            return;
        }

        // Masih ada percobaan bayar lain yang aktif? Jangan lepas slot dulu.
        $hasOtherPending = $booking->payments()
            ->where('status', 'pending')
            ->where('id', '!=', $payment->id)
            ->exists();

        if ($hasOtherPending) {
            return;
        }

        // releaseSlots() melepas slot kembali ke 'available' agar bisa
        // dipesan orang lain, dan set status booking sesuai transactionStatus.
        $booking->releaseSlots($transactionStatus === 'expire' ? 'expired' : 'cancelled');
    }

    /**
     * Uang sudah diterima tetapi slot tidak bisa diberikan ke customer.
     * Beri tahu semua admin agar mengajukan refund.
     */
    protected function flagForRefund(Payment $payment, Booking $booking): void
    {
        $notifications = app(NotificationService::class);

        User::where('role', 'admin')->each(function (User $admin) use ($notifications, $booking) {
            $notifications->send(
                $admin,
                'payment_needs_refund',
                'Pembayaran perlu di-refund',
                "Booking {$booking->booking_code} sudah dibayar tetapi slot tidak lagi tersedia. Segera ajukan refund."
            );
        });

        if ($booking->user) {
            $notifications->send(
                $booking->user,
                'payment_needs_refund',
                'Pembayaran Anda akan dikembalikan',
                "Pembayaran untuk booking {$booking->booking_code} diterima setelah slot dilepas. Tim kami akan memproses pengembalian dana."
            );
        }
    }
}
