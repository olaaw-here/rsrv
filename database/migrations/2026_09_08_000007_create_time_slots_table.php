<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('time_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resource_id')->constrained('resources')->cascadeOnDelete();
            $table->date('slot_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->decimal('price', 12, 2);
            $table->enum('status', ['available', 'held', 'booked', 'blocked'])->default('available');
            // FK ke bookings ditambahkan di migration terpisah setelah tabel bookings ada
            $table->unsignedBigInteger('held_by_booking_id')->nullable();
            $table->timestamp('held_until')->nullable();
            $table->timestamps();

            // Constraint krusial: satu resource tidak boleh punya dua baris slot
            // dengan tanggal & jam yang identik -> proteksi anti-double-booking
            // di level database, melengkapi row-lock di level aplikasi.
            $table->unique(
                ['resource_id', 'slot_date', 'start_time', 'end_time'],
                'uq_slot_resource_datetime'
            );
            $table->index(['resource_id', 'slot_date', 'status'], 'idx_slots_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_slots');
    }
};
