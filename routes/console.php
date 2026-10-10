<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Expire booking yang belum dibayar & selesaikan booking yang slotnya sudah lewat.
// Dijalankan sebagai command (sinkron), BUKAN job antrean, supaya tetap jalan
// walau queue worker belum aktif. Butuh cron: `* * * * * php artisan schedule:run`.
Schedule::command('bookings:process-lifecycle')
    ->everyMinute()
    ->withoutOverlapping(5);
