<?php

namespace App\Console\Commands;

use App\Services\BookingService;
use Illuminate\Console\Command;

class ProcessBookingLifecycle extends Command
{
    protected $signature = 'bookings:process-lifecycle';
    protected $description = 'Expire unpaid bookings and complete confirmed bookings whose slots have ended.';

    public function handle(BookingService $bookingService): int
    {
        $expired = $bookingService->expireOverdueBookings();
        $completed = $bookingService->completeDueBookings();

        $this->info("Expired: {$expired}; completed: {$completed}.");
        return self::SUCCESS;
    }
}
