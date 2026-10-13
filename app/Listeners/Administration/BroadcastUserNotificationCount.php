<?php

namespace App\Listeners\Administration;

use App\Models\Administration\UserModel;
use App\Services\Administration\UserNotificationRealtimeService;
use Illuminate\Notifications\Events\NotificationSent;

final class BroadcastUserNotificationCount
{
    public function __construct(
        private readonly UserNotificationRealtimeService $realtime,
    ) {}

    public function handle(NotificationSent $event): void
    {
        if ($event->channel !== 'database' || ! $event->notifiable instanceof UserModel) {
            return;
        }

        $notificationUuid = is_string($event->notification->id ?? null)
            ? $event->notification->id
            : null;

        $this->realtime->srv_broadcastUnreadCount($event->notifiable, $notificationUuid);
    }
}
