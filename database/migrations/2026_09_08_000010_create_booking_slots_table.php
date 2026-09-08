<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('time_slot_id')->constrained('time_slots')->cascadeOnDelete();
            $table->decimal('price_snapshot', 12, 2);

            // Satu time_slot hanya boleh muncul sekali di seluruh booking_slots
            // selama slot berstatus aktif -> proteksi tambahan anti-double-booking.
            $table->unique('time_slot_id', 'uq_bs_slot');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_slots');
    }
};
