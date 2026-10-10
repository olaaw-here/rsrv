<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class Booking extends Model
{
    protected $fillable = [
        'booking_code', 'user_id', 'resource_id', 'total_price', 'status',
        'customer_notes', 'cancellation_reason', 'expires_at', 'confirmed_at',
    ];

    protected $casts = [
        'total_price' => 'decimal:2',
        'expires_at' => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }

    public function bookingSlots(): HasMany
    {
        return $this->hasMany(BookingSlot::class);
    }

    public function timeSlots()
    {
        return $this->hasManyThrough(
            TimeSlot::class,
            BookingSlot::class,
            'booking_id',   
            'id',           
            'id',           
            'time_slot_id'  
        );
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    /**
     * Membuat booking baru dengan menahan (hold) sejumlah time slot secara
     * atomik, sesuai alur anti-double-booking pada PRD: row-level lock
     * (lockForUpdate) di dalam satu database transaction, dilengkapi
     * unique constraint di level tabel time_slots sebagai lapisan kedua.
     *
     * @param  int   $userId
     * @param  int   $resourceId
     * @param  array $timeSlotIds
     * @param  int   $holdMinutes
     * @return Booking
     *
     * @throws RuntimeException jika salah satu slot sudah tidak tersedia
     */
    public static function bookSlots(
        int $userId,
        int $resourceId,
        array $timeSlotIds,
        int $holdMinutes = 15
    ): self {
        $timeSlotIds = array_values(array_unique(array_map('intval', $timeSlotIds)));

        if ($timeSlotIds === []) {
            throw new RuntimeException('Pilih minimal satu slot.');
        }

        return DB::transaction(function () use ($userId, $resourceId, $timeSlotIds, $holdMinutes) {
            $slots = TimeSlot::whereIn('id', $timeSlotIds)
                ->where('resource_id', $resourceId)
                ->lockForUpdate()
                ->get();

            if ($slots->count() !== count($timeSlotIds)) {
                throw new RuntimeException('Beberapa slot tidak ditemukan.');
            }

            $resource = Resource::with('provider')->find($resourceId);
            if (! $resource || $resource->status !== 'active') {
                throw new RuntimeException('Resource tidak tersedia.');
            }

            if (! $resource->provider || $resource->provider->status !== 'active') {
                throw new RuntimeException('Provider resource belum aktif atau sedang ditangguhkan.');
            }

            if ($slots->contains(fn ($slot) => $slot->slot_date->lt(today()))) {
                throw new RuntimeException('Slot pada tanggal yang sudah lewat tidak dapat dipesan.');
            }

            // Bersihkan hold yang sudah kedaluwarsa sebelum mengecek ketersediaan.
            // Ini penting untuk demo/local karena scheduler tidak selalu berjalan.
            $expiredHeldIds = $slots
                ->filter(fn ($slot) =>
                    $slot->status === 'held'
                    && (! $slot->held_until || $slot->held_until->lte(now()))
                )
                ->pluck('id');

            if ($expiredHeldIds->isNotEmpty()) {
                TimeSlot::whereIn('id', $expiredHeldIds)->update([
                    'status' => 'available',
                    'held_by_booking_id' => null,
                    'held_until' => null,
                ]);

                $slots = TimeSlot::whereIn('id', $timeSlotIds)
                    ->where('resource_id', $resourceId)
                    ->lockForUpdate()
                    ->get();
            }

            $notAvailable = $slots->firstWhere('status', '!=', 'available');
            if ($notAvailable) {
                throw new RuntimeException('Slot baru saja diambil customer lain. Silakan pilih slot lain.');
            }

            $totalPrice = $slots->sum('price');
            $expiresAt = now()->addMinutes($holdMinutes);

            $booking = self::create([
                'booking_code' => 'BK-' . strtoupper(Str::random(10)),
                'user_id' => $userId,
                'resource_id' => $resourceId,
                'total_price' => $totalPrice,
                'status' => 'pending_payment',
                'expires_at' => $expiresAt,
            ]);

            TimeSlot::whereIn('id', $timeSlotIds)->update([
                'status' => 'held',
                'held_by_booking_id' => $booking->id,
                'held_until' => $expiresAt,
            ]);

            foreach ($slots as $slot) {
                BookingSlot::create([
                    'booking_id' => $booking->id,
                    'time_slot_id' => $slot->id,
                    'price_snapshot' => $slot->price,
                ]);
            }

            return $booking;
        });
    }

    /**
     * Konfirmasi booking setelah pembayaran berhasil (dipanggil dari webhook
     * handler atau konfirmasi manual provider).
     *
     * Aman terhadap kondisi balapan: booking dikunci, statusnya harus masih
     * pending_payment, dan SEMUA slot miliknya harus masih ditahan oleh
     * booking ini. Jika tidak, slot sudah dilepas/diambil orang lain dan
     * konfirmasi ditolak (bukan diam-diam menghasilkan booking tanpa slot).
     *
     * @throws RuntimeException
     */
    public function confirm(): void
    {
        DB::transaction(function () {
            $locked = static::whereKey($this->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== 'pending_payment') {
                throw new RuntimeException('Booking tidak dalam status menunggu pembayaran.');
            }

            $expected = $locked->bookingSlots()->count();
            $held = TimeSlot::where('held_by_booking_id', $locked->id)
                ->where('status', 'held')
                ->lockForUpdate()
                ->count();

            if ($expected === 0 || $held !== $expected) {
                throw new RuntimeException('Slot booking sudah dilepas atau diambil pihak lain.');
            }

            $locked->update(['status' => 'confirmed', 'confirmed_at' => now()]);

            TimeSlot::where('held_by_booking_id', $locked->id)->update([
                'status' => 'booked',
                'held_until' => null,
            ]);
        });

        $this->refresh();
    }

    /**
     * Dipakai ketika pembayaran masuk SETELAH booking expired/cancelled.
     * Jika semua slot masih available, slot diambil kembali dan booking
     * dikonfirmasi. Jika sudah diambil orang lain, kembalikan false agar
     * pemanggil bisa menandai pembayaran ini untuk di-refund.
     */
    public function reclaimSlots(): bool
    {
        $reclaimed = DB::transaction(function () {
            $locked = static::whereKey($this->id)->lockForUpdate()->firstOrFail();

            if (! in_array($locked->status, ['expired', 'cancelled'], true)) {
                return false;
            }

            $slotIds = $locked->bookingSlots()->pluck('time_slot_id');
            if ($slotIds->isEmpty()) {
                return false;
            }

            $slots = TimeSlot::whereIn('id', $slotIds)->lockForUpdate()->get();

            if ($slots->count() !== $slotIds->count()
                || $slots->contains(fn ($slot) => $slot->status !== 'available')) {
                return false;
            }

            TimeSlot::whereIn('id', $slotIds)->update([
                'status' => 'booked',
                'held_by_booking_id' => $locked->id,
                'held_until' => null,
            ]);

            $locked->update(['status' => 'confirmed', 'confirmed_at' => now()]);

            return true;
        });

        $this->refresh();

        return $reclaimed;
    }

    /**
     * Batalkan booking (pembayaran gagal/expired) dan lepas slot terkait.
     */
    public function releaseSlots(string $newStatus = 'cancelled'): void
    {
        DB::transaction(function () use ($newStatus) {
            $this->update(['status' => $newStatus]);

            TimeSlot::where('held_by_booking_id', $this->id)->update([
                'status' => 'available',
                'held_by_booking_id' => null,
                'held_until' => null,
            ]);
        });
    }
}
