<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()->appNotifications()
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'data' => NotificationResource::collection($notifications->items()),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'unread_count' => $request->user()->appNotifications()->where('is_read', false)->count(),
            ],
        ]);
    }

    public function markAsRead(Request $request, int $notificationId): JsonResponse
    {
        $notification = Notification::whereKey($notificationId)->firstOrFail();

        if ($notification->user_id !== $request->user()->id) {
            abort(403);
        }

        $notification->update(['is_read' => true]);
        return response()->json(new NotificationResource($notification));
    }
}
