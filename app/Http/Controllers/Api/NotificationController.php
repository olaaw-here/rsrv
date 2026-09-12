<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Not implemented yet'], 501);
    }

    public function markAsRead(Request $request, $notificationId): JsonResponse
    {
        return response()->json(['message' => 'Not implemented yet'], 501);
    }
}
