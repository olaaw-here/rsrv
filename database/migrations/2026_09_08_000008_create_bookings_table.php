<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_code', 30)->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('resource_id')->constrained('resources')->cascadeOnDelete();
            $table->decimal('total_price', 12, 2);
            $table->enum('status', [
                'pending_payment', 'confirmed', 'cancelled', 'expired', 'completed', 'refunded',
            ])->default('pending_payment');
            $table->text('customer_notes')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index(['status', 'expires_at'], 'idx_bookings_expires');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
