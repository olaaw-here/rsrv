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

        $slots = $resource->timeSlots()
            ->forDate($request->date)
            ->whereIn('status', ['available', 'booked', 'blocked']) // 'held' sengaja disembunyikan dari publik
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

        $hours = $resource->operationalHours->keyBy('day_of_week');
        $duration = $resource->slot_duration_minutes;
        $created = 0;

        $period = Carbon::parse($validated['from'])->toPeriod($validated['to']);

        foreach ($period as $date) {
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

        if ($slot->status === 'booked') {
            throw ValidationException::withMessages([
                'slot' => 'Slot yang sudah dipesan tidak dapat diblokir.',
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
