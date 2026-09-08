<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    public $timestamps = false;

    protected $fillable = ['name', 'slug', 'icon'];

    public function resources(): HasMany
    {
        return $this->hasMany(Resource::class);
    }
}
