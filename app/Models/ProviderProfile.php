<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProviderProfile extends Model
{
    protected $fillable = [
        'user_id', 'business_name', 'description', 'address', 'city',
        'latitude', 'longitude', 'rating_avg', 'total_reviews', 'status',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'rating_avg' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resources(): HasMany
    {
        return $this->hasMany(Resource::class, 'provider_id');
    }
}
