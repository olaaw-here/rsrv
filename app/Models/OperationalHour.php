<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperationalHour extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'resource_id', 'day_of_week', 'open_time', 'close_time', 'is_closed',
    ];

    protected $casts = [
        'is_closed' => 'boolean',
    ];

    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }
}
