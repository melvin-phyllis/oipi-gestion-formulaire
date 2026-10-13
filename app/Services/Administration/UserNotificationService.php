<?php

namespace App\Services\Administration;

use App\Dto\Administration\NotificationFiltersDTO;
use App\Models\Administration\UserModel;
use App\Repositories\Interfaces\Administration\UserNotificationRepositoryInterface;
use Illuminate\Support\Str;

final class UserNotificationService
{
    public function __construct(
        private readonly UserNotificationRepositoryInterface $notifications,
        private readonly UserNotificationRealtimeService $realtime,
    ) {}

    /** @return array<string, mixed> */
    public function srv_list(NotificationFiltersDTO $filters, UserModel $user): array
    {
        return [
            'notifications' => $this->notifications->repo_paginateForUser($user, $filters),
            'unreadNotificationsCount' => $this->notifications->repo_unreadCount($user),
            'notificationStatuses' => ['all', 'unread', 'read'],
        ];
    }

    public function srv_markRead(string $notificationId, UserModel $user): void
    {
        $notification = $this->notifications->repo_findForUser($user, $notificationId);
        $this->notifications->repo_markRead($notification);
        $this->realtime->srv_broadcastUnreadCount($user);
    }

    public function srv_markAllRead(UserModel $user): int
    {
        $count = $this->notifications->repo_markAllRead($user);
        $this->realtime->srv_broadcastUnreadCount($user);

        return $count;
    }

    /** @return array<string, string|null> */
    public function srv_notificationData(string $notificationId, UserModel $user): array
    {
        $notification = $this->notifications->repo_findForUser($user, $notificationId);
        $data = is_array($notification->data) ? $notification->data : [];

        return [
            'id' => $notification->id,
            'title' => Str::limit($this->srv_plainText((string) ($data['title'] ?? 'Notification système')), 200),
            'message' => Str::limit($this->srv_plainText((string) ($data['message'] ?? '')), 1000),
            'mission_uuid' => is_string($data['mission_uuid'] ?? null) ? $data['mission_uuid'] : null,
            'missing_form_uuid' => is_string($data['missing_form_uuid'] ?? null) ? $data['missing_form_uuid'] : null,
            'created_at' => $notification->created_at?->toIso8601String(),
        ];
    }

    public function srv_unreadCount(UserModel $user): int
    {
        return $this->notifications->repo_unreadCount($user);
    }

    private function srv_plainText(string $value): string
    {
        $withoutExecutableBlocks = preg_replace(
            '#<(script|style)\b[^>]*>.*?</\1\s*>#is',
            '',
            $value,
        ) ?? '';

        return trim(strip_tags($withoutExecutableBlocks));
    }
}
