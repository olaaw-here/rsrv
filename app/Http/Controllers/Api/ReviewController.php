<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Review\StoreReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    public function store(StoreReviewRequest $request, Booking $booking): JsonResponse
    {
        $review = DB::transaction(function () use ($request, $booking) {
            $review = $booking->review()->create([
                'user_id'     => $booking->user_id,
                'resource_id' => $booking->resource_id,
                'rating'      => $request->validated('rating'),
                'comment'     => $request->validated('comment'),
            ]);

            // Update agregat rating provider (bukan real-time murni, tapi
            // cukup akurat karena dihitung ulang dari seluruh review).
            $provider = $booking->resource->provider;
            $stats = $provider->resources()
                ->join('reviews', 'reviews.resource_id', '=', 'resources.id')
                ->selectRaw('AVG(reviews.rating) as avg_rating, COUNT(reviews.id) as total')
                ->first();

            $provider->update([
                'rating_avg'    => round($stats->avg_rating ?? 0, 2),
                'total_reviews' => $stats->total ?? 0,
            ]);

            return $review;
        });

        return response()->json(new ReviewResource($review), 201);
    }
}
