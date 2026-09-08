<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained('provider_profiles')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->string('name', 150);
            $table->enum('type', ['tempat', 'lapangan', 'konsultasi']);
            $table->text('description')->nullable();
            $table->unsignedInteger('capacity')->nullable();
            $table->unsignedInteger('slot_duration_minutes')->default(60);
            $table->decimal('base_price', 12, 2)->default(0);
            $table->enum('status', ['active', 'inactive', 'draft'])->default('draft');
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resources');
    }
};
