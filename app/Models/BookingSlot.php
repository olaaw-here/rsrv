<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingSlot extends Model
{
    public $timestamps = false;

    protected $fillable = ['booking_id', 'time_slot_id', 'price_snapshot'];

    protected $casts = [
        'price_snapshot' => 'decimal:2',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function timeSlot(): BelongsTo
    {
        return $this->belongsTo(TimeSlot::class);
    }
}
