<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TimeSlotResource;
use App\Models\Resource;
use App\Models\TimeSlot;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class TimeSlotController extends Controller
{
    /**
     * GET /api/resources/{resource}/slots?date=YYYY-MM-DD
     * Publik — dipakai customer untuk melihat kalender ketersediaan.
     */
    public function index(Request $request, Resource $resource): JsonResponse
    {
        $request->validate(['date' => ['required', 'date']]);

        // Normalisasi ke Y-m-d agar input seperti "2026-10-12T00:00" tetap cocok.
        $date = Carbon::parse($request->date)->toDateString();

        abort_unless(
            $resource->status === 'active'
                && $resource->provider?->status === 'active',
            404
        );

        // Hold yang sudah lewat harus kembali terlihat sebagai available,
        // walaupun scheduler belum sempat menjalankan expiry job.
        TimeSlot::where('resource_id', $resource->id)
            ->whereDate('slot_date', $date)
            ->where('status', 'held')
            ->where(function ($q) {
                $q->whereNull('held_until')
                  ->orWhere('held_until', '<=', now());
            })
            ->update([
                'status' => 'available',
                'held_by_booking_id' => null,
                'held_until' => null,
            ]);

        $slots = $resource->timeSlots()
            ->forDate($date)
            ->whereIn('status', ['available', 'booked', 'blocked'])
            ->orderBy('start_time')
            ->get();

        return response()->json(TimeSlotResource::collection($slots));
    }

    /**
     * POST /provider/resources/{resource}/slots/generate
     * Generate baris time_slots untuk rentang tanggal, berdasarkan
     * operational_hours + slot_duration_minutes milik resource.
     */
    public function generate(Request $request, Resource $resource): JsonResponse
    {
        $this->authorizeOwnership($request, $resource);

        $validated = $request->validate([
            'from' => ['required', 'date'],
            'to'   => ['required', 'date', 'after_or_equal:from'],
        ]);

        $from = Carbon::parse($validated['from'])->startOfDay();
        $to = Carbon::parse($validated['to'])->startOfDay();

        // Batasi rentang agar satu request tidak menghasilkan puluhan ribu query.
        if ($from->diffInDays($to) > 92) {
            throw ValidationException::withMessages([
                'to' => 'Rentang generate slot maksimal 92 hari.',
            ]);
        }

        $hours = $resource->operationalHours->keyBy('day_of_week');
        $duration = $resource->slot_duration_minutes;
        $created = 0;

        $period = $from->toPeriod($to);

        foreach ($period as $date) {
            // Jangan membuat slot di tanggal yang sudah lewat.
            if ($date->lt(today())) {
                continue;
            }

            $dayHour = $hours->get($date->dayOfWeek);

            if (! $dayHour || $dayHour->is_closed || ! $dayHour->open_time || ! $dayHour->close_time) {
                continue;
            }

            $cursor = Carbon::parse($date->format('Y-m-d') . ' ' . $dayHour->open_time);
            $close = Carbon::parse($date->format('Y-m-d') . ' ' . $dayHour->close_time);

            while ($cursor->copy()->addMinutes($duration)->lte($close)) {
                $slotEnd = $cursor->copy()->addMinutes($duration);

                $slot = TimeSlot::firstOrCreate(
                    [
                        'resource_id' => $resource->id,
                        'slot_date'   => $date->format('Y-m-d'),
                        'start_time'  => $cursor->format('H:i:s'),
                        'end_time'    => $slotEnd->format('H:i:s'),
                    ],
                    ['price' => $resource->base_price, 'status' => 'available']
                );

                if ($slot->wasRecentlyCreated) {
                    $created++;
                }

                $cursor = $slotEnd;
            }
        }

        return response()->json(['message' => "Berhasil generate {$created} slot baru."]);
    }

    /**
     * POST /provider/resources/{resource}/slots/{slot}/block
     */
    public function block(Request $request, Resource $resource, TimeSlot $slot): JsonResponse
    {
        $this->authorizeOwnership($request, $resource);
        $this->ensureSlotBelongsToResource($resource, $slot);

        if ($slot->status !== 'available') {
            throw ValidationException::withMessages([
                'slot' => 'Hanya slot yang tersedia yang dapat diblokir.',
            ]);
        }

        $slot->update(['status' => 'blocked']);

        return response()->json(new TimeSlotResource($slot));
    }

    /**
     * POST /provider/resources/{resource}/slots/{slot}/unblock
     */
    public function unblock(Request $request, Resource $resource, TimeSlot $slot): JsonResponse
    {
        $this->authorizeOwnership($request, $resource);
        $this->ensureSlotBelongsToResource($resource, $slot);

        if ($slot->status !== 'blocked') {
            throw ValidationException::withMessages([
                'slot' => 'Hanya slot berstatus blocked yang dapat dibuka kembali.',
            ]);
        }

        $slot->update(['status' => 'available']);

        return response()->json(new TimeSlotResource($slot));
    }

    protected function authorizeOwnership(Request $request, Resource $resource): void
    {
        $providerProfile = $request->user()->providerProfile;

        if (! $providerProfile || $resource->provider_id !== $providerProfile->id) {
            abort(403, 'Resource ini bukan milik Anda.');
        }
    }

    protected function ensureSlotBelongsToResource(Resource $resource, TimeSlot $slot): void
    {
        if ($slot->resource_id !== $resource->id) {
            abort(404);
        }
    }
}
