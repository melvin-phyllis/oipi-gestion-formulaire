<?php

namespace App\Events\Administration;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class UserNotificationCountUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $userUuid,
        public readonly int $unreadCount,
        public readonly ?string $notificationUuid = null,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("user-notifications.{$this->userUuid}");
    }

    public function broadcastAs(): string
    {
        return 'user.notification.count.updated';
    }

    /** @return array{user_uuid: string, unread_count: int, notification_uuid: string|null} */
    public function broadcastWith(): array
    {
        return [
            'user_uuid' => $this->userUuid,
            'unread_count' => $this->unreadCount,
            'notification_uuid' => $this->notificationUuid,
        ];
    }
}
