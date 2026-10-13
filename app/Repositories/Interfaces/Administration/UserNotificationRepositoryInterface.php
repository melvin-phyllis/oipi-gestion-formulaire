<?php

namespace App\Repositories\Interfaces\Administration;

use App\Dto\Administration\NotificationFiltersDTO;
use App\Models\Administration\UserModel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Notifications\DatabaseNotification;

interface UserNotificationRepositoryInterface
{
    /** @return LengthAwarePaginator<int, DatabaseNotification> */
    public function repo_paginateForUser(
        UserModel $user,
        NotificationFiltersDTO $filters,
    ): LengthAwarePaginator;

    public function repo_findForUser(UserModel $user, string $notificationId): DatabaseNotification;

    public function repo_markRead(DatabaseNotification $notification): void;

    public function repo_markAllRead(UserModel $user): int;

    public function repo_unreadCount(UserModel $user): int;
}
