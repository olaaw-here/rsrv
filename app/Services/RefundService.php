<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Midtrans\Config as MidtransConfig;
use Midtrans\Transaction;
use RuntimeException;

class RefundService
{
    public function __construct()
    {
        MidtransConfig::$serverKey = config('services.midtrans.server_key');
        MidtransConfig::$isProduction = config('services.midtrans.is_production', false);
        MidtransConfig::$isSanitized = true;
        MidtransConfig::$is3ds = true;
    }

    /**
     * Submit a new refund request to Midtrans. The local refund remains
     * `requested` until an admin confirms the refund has actually settled.
     */
    public function request(Payment $payment, float $amount, ?string $reason): Refund
    {
        return DB::transaction(function () use ($payment, $amount, $reason) {
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->with('booking.user')->firstOrFail();

            if ($payment->status !== 'settlement') {
                throw new RuntimeException('Refund hanya dapat diajukan untuk pembayaran yang sudah settlement.');
            }

            $existing = $payment->refund()->lockForUpdate()->first();
            if ($existing && in_array($existing->status, ['requested', 'processed'], true)) {
                throw new RuntimeException('Refund untuk pembayaran ini sudah pernah diajukan.');
            }

            if ($amount <= 0 || $amount > (float) $payment->amount) {
                throw new RuntimeException('Nominal refund tidak valid.');
            }

            $refundKey = 'RF-' . $payment->id . '-' . strtoupper(Str::random(10));
            Transaction::refund($payment->transaction_id, [
                'refund_key' => $refundKey,
                'amount' => (int) round($amount),
                'reason' => $reason ?: 'Refund booking RSRV',
            ]);

            return $payment->refund()->updateOrCreate([], [
                'refund_key' => $refundKey,
                'amount' => $amount,
                'reason' => $reason,
                'status' => 'requested',
            ]);
        });
    }

    /**
     * Complete a refund after the gateway/bank confirmation has been received.
     * This method changes local financial state; it does not claim to call the
     * gateway itself.
     */
    public function markProcessed(Refund $refund, User $admin): Refund
    {
        return DB::transaction(function () use ($refund, $admin) {
            $refund = Refund::whereKey($refund->id)->lockForUpdate()->with('payment.booking.user')->firstOrFail();

            if ($refund->status !== 'requested') {
                throw new RuntimeException('Refund ini tidak berada pada status requested.');
            }

            $refund->update([
                'status' => 'processed',
                'processed_by' => $admin->id,
                'processed_at' => now(),
            ]);

            $refund->payment->update(['status' => 'refunded']);
            $refund->payment->booking->update(['status' => 'refunded']);

            app(NotificationService::class)->send(
                $refund->payment->booking->user,
                'refund_processed',
                'Refund selesai',
                "Refund untuk booking {$refund->payment->booking->booking_code} telah diproses."
            );

            return $refund->fresh(['payment.booking']);
        });
    }

    public function reject(Refund $refund, User $admin, ?string $reason = null): Refund
    {
        return DB::transaction(function () use ($refund, $admin, $reason) {
            $refund = Refund::whereKey($refund->id)->lockForUpdate()->with('payment.booking.user')->firstOrFail();

            if ($refund->status !== 'requested') {
                throw new RuntimeException('Refund ini tidak berada pada status requested.');
            }

            $refund->update([
                'status' => 'rejected',
                'processed_by' => $admin->id,
                'processed_at' => now(),
                'reason' => trim(($refund->reason ? $refund->reason . "\n" : '') . ($reason ?: 'Refund ditolak oleh admin.')),
            ]);

            if ($refund->payment->status === 'refunded') {
                $refund->payment->update(['status' => 'settlement']);
            }
            if ($refund->payment->booking->status === 'refunded') {
                $refund->payment->booking->update(['status' => 'completed']);
            }

            app(NotificationService::class)->send(
                $refund->payment->booking->user,
                'refund_rejected',
                'Refund ditolak',
                "Refund untuk booking {$refund->payment->booking->booking_code} ditolak."
            );

            return $refund->fresh(['payment.booking']);
        });
    }
}
