<?php

namespace App\Jobs;

use App\Services\BookingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ExpireBookingJob implements ShouldQueue
{
    use Queueable;

    public function handle(BookingService $bookingService): void
    {
        $bookingService->expireOverdueBookings();
        $bookingService->completeDueBookings();
    }
}
