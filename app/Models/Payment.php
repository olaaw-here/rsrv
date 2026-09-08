<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Payment extends Model
{
    protected $fillable = [
        'booking_id', 'gateway', 'transaction_id', 'payment_method', 'amount',
        'status', 'snap_token', 'payment_url', 'paid_at', 'expired_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'expired_at' => 'datetime',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(PaymentLog::class);
    }

    public function refund(): HasOne
    {
        return $this->hasOne(Refund::class);
    }

    /**
     * Cek apakah sebuah notification_id dari payment gateway sudah pernah
     * diproses sebelumnya — dasar dari penanganan webhook yang idempoten.
     */
    public function hasProcessedNotification(?string $notificationId): bool
    {
        if (! $notificationId) {
            return false;
        }

        return $this->logs()->where('notification_id', $notificationId)->exists();
    }
}
