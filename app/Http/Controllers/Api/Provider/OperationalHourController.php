<?php

namespace App\Http\Controllers\Api\Provider;

use App\Http\Controllers\Controller;
use App\Models\Resource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class OperationalHourController extends Controller
{
    public function index(Request $request, Resource $resource): JsonResponse
    {
        $this->authorizeOwnership($request, $resource);

        return response()->json($resource->operationalHours()->orderBy('day_of_week')->get());
    }

    public function update(Request $request, Resource $resource): JsonResponse
    {
        $this->authorizeOwnership($request, $resource);

        $validated = $request->validate([
            'hours'                    => ['required', 'array', 'size:7'],
            'hours.*.day_of_week'      => ['required', 'integer', 'between:0,6', 'distinct'],
            'hours.*.open_time'        => ['nullable', 'date_format:H:i'],
            'hours.*.close_time'       => ['nullable', 'date_format:H:i', 'after:hours.*.open_time'],
            'hours.*.is_closed'        => ['required', 'boolean'],
        ]);

        foreach ($validated['hours'] as $hour) {
            $resource->operationalHours()->updateOrCreate(
                ['day_of_week' => $hour['day_of_week']],
                [
                    'open_time'  => $hour['is_closed'] ? null : $hour['open_time'],
                    'close_time' => $hour['is_closed'] ? null : $hour['close_time'],
                    'is_closed'  => $hour['is_closed'],
                ]
            );
        }

        return response()->json($resource->operationalHours()->orderBy('day_of_week')->get());
    }

    protected function authorizeOwnership(Request $request, Resource $resource): void
    {
        $providerProfile = $request->user()->providerProfile;

        if (! $providerProfile || $resource->provider_id !== $providerProfile->id) {
            abort(403, 'Resource ini bukan milik Anda.');
        }
    }
}
