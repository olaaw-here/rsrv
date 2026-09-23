<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TimeSlot extends Model
{
    use HasFactory;
    protected $fillable = [
        'resource_id', 'slot_date', 'start_time', 'end_time', 'price',
        'status', 'held_by_booking_id', 'held_until',
    ];

    protected $casts = [
        'slot_date' => 'date',
        'price' => 'decimal:2',
        'held_until' => 'datetime',
    ];

    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }

    public function heldByBooking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'held_by_booking_id');
    }

    public function bookingSlot(): HasOne
    {
        return $this->hasOne(BookingSlot::class);
    }

    // Scopes
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    public function scopeForDate($query, string $date)
    {
        return $query->where('slot_date', $date);
    }
}
