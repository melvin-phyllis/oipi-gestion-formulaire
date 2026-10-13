<?php

namespace App\Repositories\Administration;

use App\Dto\Administration\NotificationFiltersDTO;
use App\Models\Administration\UserModel;
use App\Repositories\Interfaces\Administration\UserNotificationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Notifications\DatabaseNotification;

final class UserNotificationRepository implements UserNotificationRepositoryInterface
{
    public function repo_paginateForUser(
        UserModel $user,
        NotificationFiltersDTO $filters,
    ): LengthAwarePaginator {
        return $user->notifications()
            ->when($filters->status === 'unread', fn ($query) => $query->whereNull('read_at'))
            ->when($filters->status === 'read', fn ($query) => $query->whereNotNull('read_at'))
            ->latest()
            ->paginate($filters->perPage)
            ->withQueryString();
    }

    public function repo_findForUser(UserModel $user, string $notificationId): DatabaseNotification
    {
        return $user->notifications()->whereKey($notificationId)->firstOrFail();
    }

    public function repo_markRead(DatabaseNotification $notification): void
    {
        if ($notification->read_at === null) {
            $notification->markAsRead();
        }
    }

    public function repo_markAllRead(UserModel $user): int
    {
        return $user->unreadNotifications()->update(['read_at' => now()]);
    }

    public function repo_unreadCount(UserModel $user): int
    {
        return $user->unreadNotifications()->count();
    }
}
