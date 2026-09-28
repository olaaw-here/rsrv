<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

class NotificationService
{
    public function send(
        User $user,
        string $type,
        string $title,
        string $message
    ): Notification {
        return $user->appNotifications()->create([
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'is_read' => false,
        ]);
    }
}
