<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\User;
use App\Models\Resource;
use RuntimeException;
use Illuminate\Support\Facades\Route;

class BookingService 
{
    public function __construct(protected PaymentServices $PaymentServices)
    {
    }

    public function create(User $user, array $data): array
    {
        $booking = Booking::create([
            'user_id' => $user->id,
            'resource_id' => $data['resource_id'],
            'customer_notes' => $data['customer_notes'] ?? null,
        ]);

        if (! empty($data['customer_notes'])) {
            $booking->customer_notes = $data['customer_notes'];
        }

        $payment = $this->PaymentServices->initiatePayment($booking);

        return [
            'booking' => $booking->fresh(['bookingSlots.timeSlot', 'resource']),
            'payment' => $payment,
        ];
    }

    public function cancel(Booking $booking): Booking
    {
        if (! in_array($booking->status, ['pending_payment', 'confirmed'])) {
            throw new RuntimeException('Booking dengan status ini tidak dapat dibatalkan.');
        }

        $newStatus = $booking->status === 'confirmed' ? 'cancelled' : 'cancelled';

        $booking->releaseSlots($newStatus);

        return $booking->fresh();
    }

    public function expireOverdueBookings(): int
    {
        $overdue = Booking::where('status', 'pending_payment')
            ->where('expires_at', '<', now())
            ->get();

        foreach ($overdue as $booking) {
            $booking->releaseSlots('expired');
        }

        return $overdue->count();
    }
}

