<?php

namespace App\Services;

use App\Exceptions\PaymentGatewayException;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class BookingService
{
    public function __construct(protected PaymentService $paymentService)
    {
    }

    /**
     * Buat booking (hold slot) lalu siapkan pembayaran Midtrans.
     *
     * @return array{booking: Booking, payment: \App\Models\Payment}
     *
     * @throws RuntimeException          slot tidak tersedia / tidak valid (HTTP 409)
     * @throws PaymentGatewayException   gateway gagal; slot sudah dilepas lagi (HTTP 502)
     */
    public function create(User $user, array $data): array
    {
        $booking = Booking::bookSlots(
            $user->id,
            (int) $data['resource_id'],
            array_map('intval', $data['time_slot_ids']),
            15
        );

        if (! empty($data['customer_notes'])) {
            $booking->update(['customer_notes' => $data['customer_notes']]);
        }

        try {
            $payment = $this->paymentService->initiateForBooking(
                $booking->fresh(['bookingSlots.timeSlot', 'resource', 'user'])
            );
        } catch (Throwable $e) {
            // Pembayaran gagal dibuat: lepaskan slot agar tidak tertahan sia-sia.
            $booking->releaseSlots('cancelled');

            report($e);

            throw new PaymentGatewayException(
                'Gagal membuat transaksi pembayaran. Silakan coba lagi.',
                0,
                $e
            );
        }

        return [
            'booking' => $booking->fresh(['bookingSlots.timeSlot', 'resource', 'payments']),
            'payment' => $payment,
        ];
    }

    public function cancel(Booking $booking, string $reason): Booking
    {
        $cancelledPayments = collect();

        $booking = DB::transaction(function () use ($booking, $reason, &$cancelledPayments) {
            $locked = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== 'pending_payment') {
                throw new RuntimeException('Booking hanya dapat dibatalkan sebelum pembayaran berhasil.');
            }

            $locked->update(['cancellation_reason' => $reason]);

            $cancelledPayments = $locked->payments()->where('status', 'pending')->get();
            $locked->payments()->where('status', 'pending')->update(['status' => 'cancel']);

            $locked->releaseSlots('cancelled');

            return $locked;
        });

        // Di luar transaksi: panggilan jaringan ke Midtrans tidak boleh menahan lock DB.
        // Tujuannya agar customer tidak bisa membayar booking yang sudah dibatalkan.
        foreach ($cancelledPayments as $payment) {
            $this->paymentService->cancelGatewayTransaction($payment);
        }

        app(NotificationService::class)->send(
            $booking->user,
            'booking_cancelled',
            'Booking dibatalkan',
            "Booking {$booking->booking_code} telah dibatalkan."
        );

        return $booking->fresh();
    }

    public function completeDueBookings(): int
    {
        $count = 0;

        Booking::where('status', 'confirmed')
            ->with('bookingSlots.timeSlot')
            ->chunkById(100, function ($bookings) use (&$count) {
                foreach ($bookings as $booking) {
                    $lastEnd = $booking->bookingSlots
                        ->map(fn ($bookingSlot) => $bookingSlot->timeSlot?->slot_date?->copy()->setTimeFromTimeString($bookingSlot->timeSlot->end_time))
                        ->filter()
                        ->max();

                    if ($lastEnd && $lastEnd->lte(now())) {
                        $booking->update(['status' => 'completed']);
                        $count++;
                    }
                }
            });

        return $count;
    }

    public function expireOverdueBookings(): int
    {
        $count = 0;

        Booking::where('status', 'pending_payment')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->chunkById(100, function ($bookings) use (&$count) {
                foreach ($bookings as $booking) {
                    $expired = DB::transaction(function () use ($booking) {
                        $locked = Booking::whereKey($booking->id)->lockForUpdate()->first();

                        if (! $locked
                            || $locked->status !== 'pending_payment'
                            || ! $locked->expires_at?->lte(now())) {
                            return null;
                        }

                        $locked->payments()->where('status', 'pending')->update(['status' => 'expire']);
                        $locked->releaseSlots('expired');

                        return $locked;
                    });

                    if (! $expired) {
                        continue;
                    }

                    $count++;

                    if ($expired->user) {
                        app(NotificationService::class)->send(
                            $expired->user,
                            'booking_expired',
                            'Booking kedaluwarsa',
                            "Booking {$expired->booking_code} kedaluwarsa karena pembayaran belum diterima."
                        );
                    }
                }
            });

        return $count;
    }
}
