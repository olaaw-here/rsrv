<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Resource extends Model
{
    protected $fillable = [
        'provider_id', 'category_id', 'name', 'type', 'description',
        'capacity', 'slot_duration_minutes', 'base_price', 'status',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(ProviderProfile::class, 'provider_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ResourceImage::class)->orderBy('sort_order');
    }

    public function operationalHours(): HasMany
    {
        return $this->hasMany(OperationalHour::class);
    }

    public function timeSlots(): HasMany
    {
        return $this->hasMany(TimeSlot::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
