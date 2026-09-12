<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BookingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Not implemented yet'], 501);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Not implemented yet'], 501);
    }

    public function show(Request $request, Booking $booking): JsonResponse
    {
        return response()->json(['message' => 'Not implemented yet'], 501);
    }

    public function cancel(Request $request, Booking $booking): JsonResponse
    {
        return response()->json(['message' => 'Not implemented yet'], 501);
    }

    public function initiatePayment(Request $request, Booking $booking): JsonResponse
    {
        return response()->json(['message' => 'Not implemented yet'], 501);
    }
}
