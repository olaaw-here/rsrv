<?php

namespace App\Services;

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
            $payment = $this->paymentService->initiateForBooking($booking->fresh(['bookingSlots.timeSlot', 'resource', 'user']));
        } catch (Throwable $e) {
            $booking->releaseSlots('cancelled');
            throw new RuntimeException('Gagal membuat transaksi pembayaran. Silakan coba lagi.', 0, $e);
        }

        return [
            'booking' => $booking->fresh(['bookingSlots.timeSlot', 'resource', 'payments']),
            'payment' => $payment,
        ];
    }

    public function cancel(Booking $booking): Booking
    {
        if ($booking->status !== 'pending_payment') {
            throw new RuntimeException('Booking hanya dapat dibatalkan sebelum pembayaran berhasil.');
        }

        $booking->releaseSlots('cancelled');
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
                    DB::transaction(function () use ($booking) {
                        $locked = Booking::whereKey($booking->id)->lockForUpdate()->first();
                        if ($locked && $locked->status === 'pending_payment' && $locked->expires_at?->lte(now())) {
                            $locked->releaseSlots('expired');
                        }
                    });
                    $count++;
                }
            });
        return $count;
    }
}
