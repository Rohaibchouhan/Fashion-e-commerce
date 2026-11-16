<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\CustomerInAppNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class NotificationService
{
    public function getNotificationsForUser(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return $user->notifications()->latest()->paginate($perPage);
    }

    public function getUnreadCount(User $user): int
    {
        return $user->unreadNotifications()->count();
    }

    public function markAsRead(User $user, string $notificationId): bool
    {
        $notification = $user->notifications()->where('id', $notificationId)->first();
        if ($notification) {
            $notification->markAsRead();
            return true;
        }
        return false;
    }

    public function markAllAsRead(User $user): void
    {
        $user->unreadNotifications->markAsRead();
    }

    public function sendCustomerNotification(User $user, string $title, string $message, ?string $link = null, ?string $type = 'info'): void
    {
        $user->notify(new CustomerInAppNotification($title, $message, $link, $type));
    }
}

