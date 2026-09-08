<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('time_slots', function (Blueprint $table) {
            $table->foreign('held_by_booking_id', 'fk_slots_held_booking')
                ->references('id')->on('bookings')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('time_slots', function (Blueprint $table) {
            $table->dropForeign('fk_slots_held_booking');
        });
    }
};
