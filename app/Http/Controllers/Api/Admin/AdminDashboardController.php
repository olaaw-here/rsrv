<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\ProviderProfile;
use App\Models\Refund;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function summary(): JsonResponse
    {
        $booking = Booking::selectRaw("COUNT(*) as total")
            ->selectRaw("SUM(CASE WHEN status = 'pending_payment' THEN 1 ELSE 0 END) as pending_payment")
            ->selectRaw("SUM(CASE WHEN status IN ('confirmed','completed') THEN 1 ELSE 0 END) as active_or_completed")
            ->selectRaw("COALESCE(SUM(CASE WHEN status IN ('confirmed','completed') THEN total_price ELSE 0 END), 0) as gross_booking_value")
            ->first();

        return response()->json([
            'providers_pending' => ProviderProfile::where('status', 'pending')->count(),
            'providers_active' => ProviderProfile::where('status', 'active')->count(),
            'total_bookings' => (int) ($booking->total ?? 0),
            'pending_payment' => (int) ($booking->pending_payment ?? 0),
            'active_or_completed' => (int) ($booking->active_or_completed ?? 0),
            'gross_booking_value' => (float) ($booking->gross_booking_value ?? 0),
            'refunds_requested' => Refund::where('status', 'requested')->count(),
            'payments_settled' => Payment::where('status', 'settlement')->count(),
        ]);
    }

    public function recentBookings(): JsonResponse
    {
        $bookings = Booking::with(['resource', 'user', 'payments'])
            ->latest()
            ->limit(15)
            ->get();

        return response()->json(['data' => $bookings]);
    }
}
