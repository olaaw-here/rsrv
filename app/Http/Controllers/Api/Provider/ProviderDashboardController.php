<?php

namespace App\Http\Controllers\Api\Provider;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Models\Resource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ProviderDashboardController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        $providerProfile = $request->user()->providerProfile;
        $resourceIds = $providerProfile->resources()->pluck('id');

        $totals = DB::table('bookings')
            ->whereIn('resource_id', $resourceIds)
            ->selectRaw("
                COUNT(*) FILTER (WHERE status IN ('confirmed','completed')) as total_booking,
                COALESCE(SUM(total_price) FILTER (WHERE status IN ('confirmed','completed')), 0) as total_pendapatan,
                COUNT(*) FILTER (WHERE status = 'pending_payment') as pending_payment
            ")
            ->first();
    
        return response()->json([
            'total_booking'    => $totals->total_booking ?? 0,
            'total_pendapatan' => (float) ($totals->total_pendapatan ?? 0),
            'pending_payment'  => $totals->pending_payment ?? 0,
            'rating_avg'       => (float) $providerProfile->rating_avg,
            'total_reviews'    => $providerProfile->total_reviews,
        ]);
    }

    public function bookings(Request $request): JsonResponse
    {
        $providerProfile = $request->user()->providerProfile;
        $resourceIds = $providerProfile->resources()->pluck('id');

        $bookings = \App\Models\Booking::whereIn('resource_id', $resourceIds)
            ->with(['resource', 'user', 'bookingSlots.timeSlot'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->to))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'data' => BookingResource::collection($bookings->items()),
            'meta' => [
                'current_page' => $bookings->currentPage(),
                'last_page'    => $bookings->lastPage(),
                'total'        => $bookings->total(),
            ],
        ]);
    }

    public function exportBookings(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Export belum diimplementasikan — lihat TODO di controller.'], 501);
    }

    public function occupancy(Request $request, Resource $resource): JsonResponse
    {
        $providerProfile = $request->user()->providerProfile;

        if ($resource->provider_id !== $providerProfile->id) {
            abort(403, 'Resource ini bukan milik Anda.');
        }

        $validated = $request->validate([
            'from' => ['required', 'date'],
            'to'   => ['required', 'date', 'after_or_equal:from'],
        ]);

        $occupancy = $resource->timeSlots()
            ->whereBetween('slot_date', [$validated['from'], $validated['to']])
            ->selectRaw('slot_date, COUNT(*) as total_slot, SUM(CASE WHEN status = "booked" THEN 1 ELSE 0 END) as slot_terisi')
            ->groupBy('slot_date')
            ->orderBy('slot_date')
            ->get();

        return response()->json($occupancy);
    }
}
