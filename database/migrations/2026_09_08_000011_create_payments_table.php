<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->string('gateway', 30)->comment('midtrans, xendit, tripay');
            $table->string('transaction_id', 100)->unique();
            $table->string('payment_method', 50)->nullable();
            $table->decimal('amount', 12, 2);
            $table->enum('status', [
                'pending', 'settlement', 'expire', 'cancel', 'deny', 'refunded',
            ])->default('pending');
            $table->string('snap_token')->nullable();
            $table->string('payment_url')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
