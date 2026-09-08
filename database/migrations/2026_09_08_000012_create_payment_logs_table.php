<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->string('event_type', 50);
            $table->string('notification_id', 100)->nullable()
                ->comment('ID unik notifikasi dari gateway, untuk cek duplikasi/idempotency');
            $table->json('raw_payload');
            $table->boolean('signature_valid')->default(false);
            $table->timestamp('received_at')->useCurrent();

            $table->unique('notification_id', 'uq_logs_notification');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_logs');
    }
};
