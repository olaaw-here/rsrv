<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ReviewController extends Controller
{
    public function store(Request $request, Booking $booking): JsonResponse
    {
        return response()->json(['message' => 'Not implemented yet'], 501);
    }
}
