<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Review\StoreReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Booking;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    public function store(StoreReviewRequest $request, Booking $booking, NotificationService $notifications): JsonResponse
    {
        if ($booking->user_id !== $request->user()->id) {
            abort(403, 'Anda tidak memiliki akses ke booking ini.');
        }

        if ($booking->status !== 'completed') {
            return response()->json(['message' => 'Review hanya dapat dibuat setelah booking selesai.'], 422);
        }

        if ($booking->review()->exists()) {
            return response()->json(['message' => 'Booking ini sudah memiliki review.'], 409);
        }

        $review = DB::transaction(function () use ($request, $booking, $notifications) {
            $review = $booking->review()->create([
                'user_id' => $booking->user_id,
                'resource_id' => $booking->resource_id,
                'rating' => $request->validated('rating'),
                'comment' => $request->validated('comment'),
            ]);

            $provider = $booking->resource->provider;
            $stats = $provider->resources()
                ->join('reviews', 'reviews.resource_id', '=', 'resources.id')
                ->selectRaw('AVG(reviews.rating) as avg_rating, COUNT(reviews.id) as total')
                ->first();

            $provider->update([
                'rating_avg' => round((float) ($stats->avg_rating ?? 0), 2),
                'total_reviews' => (int) ($stats->total ?? 0),
            ]);

            $notifications->send(
                $provider->user,
                'new_review',
                'Review baru diterima',
                "Customer memberikan rating {$review->rating}/5 untuk {$booking->resource->name}."
            );

            return $review;
        });

        return response()->json(new ReviewResource($review), 201);
    }
}
