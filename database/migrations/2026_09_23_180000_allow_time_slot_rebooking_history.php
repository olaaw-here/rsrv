<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_slots', function (Blueprint $table) {
            // Foreign key menggunakan index uq_bs_slot,
            // jadi foreign key harus dilepas terlebih dahulu.
            $table->dropForeign(['time_slot_id']);

            // Hapus unique constraint karena satu time slot
            // boleh muncul kembali pada histori booking setelah dibatalkan/expired.
            $table->dropUnique('uq_bs_slot');

            // Tetap pertahankan index biasa untuk performa query.
            $table->index('time_slot_id', 'idx_bs_time_slot');

            // Pasang kembali foreign key.
            $table->foreign('time_slot_id')
                ->references('id')
                ->on('time_slots')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('booking_slots', function (Blueprint $table) {
            $table->dropForeign(['time_slot_id']);
            $table->dropIndex('idx_bs_time_slot');

            $table->unique('time_slot_id', 'uq_bs_slot');

            $table->foreign('time_slot_id')
                ->references('id')
                ->on('time_slots')
                ->cascadeOnDelete();
        });
    }
};