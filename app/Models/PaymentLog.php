<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'payment_id', 'event_type', 'notification_id', 'raw_payload',
        'signature_valid', 'received_at',
    ];

    protected $casts = [
        'raw_payload' => 'array',
        'signature_valid' => 'boolean',
        'received_at' => 'datetime',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
