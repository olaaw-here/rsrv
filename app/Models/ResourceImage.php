<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResourceImage extends Model
{
    public $timestamps = false;

    protected $fillable = ['resource_id', 'url', 'sort_order'];

    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }
}
