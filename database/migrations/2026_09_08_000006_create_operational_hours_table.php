<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operational_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resource_id')->constrained('resources')->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week')->comment('0=Minggu ... 6=Sabtu');
            $table->time('open_time')->nullable();
            $table->time('close_time')->nullable();
            $table->boolean('is_closed')->default(false);

            $table->unique(['resource_id', 'day_of_week'], 'uq_hours_resource_day');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operational_hours');
    }
};
