<?php

namespace App\Services\Administration;

use App\Enums\Administration\PermissionCode;
use App\Events\Administration\UserNotificationCountUpdated;
use App\Models\Administration\UserModel;
use App\Repositories\Interfaces\Administration\UserNotificationRepositoryInterface;
use Throwable;

final class UserNotificationRealtimeService
{
    public function __construct(
        private readonly UserNotificationRepositoryInterface $notifications,
    ) {}

    public function srv_broadcastUnreadCount(
        UserModel $user,
        ?string $notificationUuid = null,
    ): void {
        if (! $user->can(PermissionCode::NotificationsView->value)) {
            return;
        }

        try {
            UserNotificationCountUpdated::dispatch(
                $user->uuid,
                $this->notifications->repo_unreadCount($user),
                $notificationUuid,
            );
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
