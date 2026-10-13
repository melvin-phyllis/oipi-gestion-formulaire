<?php

namespace Tests\Feature\Administration;

use App\Dto\Administration\NotificationFiltersDTO;
use App\Enums\Administration\RoleCode;
use App\Events\Administration\UserNotificationCountUpdated;
use App\Listeners\Administration\BroadcastUserNotificationCount;
use App\Models\Administration\RoleModel;
use App\Models\Administration\UserModel;
use App\Repositories\Interfaces\Administration\UserNotificationRepositoryInterface;
use App\Services\Administration\UserNotificationService;
use Database\Seeders\Administration\PermissionSeeder;
use Database\Seeders\Administration\RolePermissionSeeder;
use Database\Seeders\Administration\RoleSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

final class UserNotificationBackendTest extends TestCase
{
    use RefreshDatabase;

    private UserNotificationService $service;

    private UserModel $user;

    private UserModel $otherUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);

        $roleCodeUnique = RoleModel::query()
            ->where('role_code', RoleCode::SearchAgent->value)
            ->valueOrFail('role_code_unique');
        $this->user = UserModel::factory()->create(['role_code_unique' => $roleCodeUnique]);
        $this->otherUser = UserModel::factory()->create(['role_code_unique' => $roleCodeUnique]);
        $this->service = app(UserNotificationService::class);
    }

    public function test_list_contains_only_the_authenticated_users_notifications(): void
    {
        $unread = $this->createNotification($this->user);
        $this->createNotification($this->user, now());
        $this->createNotification($this->otherUser);

        $data = $this->service->srv_list(
            new NotificationFiltersDTO(status: 'unread'),
            $this->user,
        );

        $this->assertSame(1, $data['notifications']->total());
        $this->assertTrue($data['notifications']->first()->is($unread));
        $this->assertSame(1, $data['unreadNotificationsCount']);
    }

    public function test_user_can_mark_own_notification_and_then_all_others_as_read(): void
    {
        $first = $this->createNotification($this->user);
        $second = $this->createNotification($this->user);

        $this->service->srv_markRead($first->id, $this->user);

        $this->assertNotNull($first->refresh()->read_at);
        $this->assertSame(1, $this->service->srv_markAllRead($this->user));
        $this->assertNotNull($second->refresh()->read_at);
        $this->assertSame(0, $this->service->srv_markAllRead($this->user));
    }

    public function test_read_actions_broadcast_the_updated_unread_count(): void
    {
        Event::fake([UserNotificationCountUpdated::class]);
        $first = $this->createNotification($this->user);
        $this->createNotification($this->user);

        $this->service->srv_markRead($first->id, $this->user);

        Event::assertDispatched(
            UserNotificationCountUpdated::class,
            fn (UserNotificationCountUpdated $event): bool => $event->userUuid === $this->user->uuid
                && $event->unreadCount === 1,
        );

        $this->service->srv_markAllRead($this->user);

        Event::assertDispatched(
            UserNotificationCountUpdated::class,
            fn (UserNotificationCountUpdated $event): bool => $event->userUuid === $this->user->uuid
                && $event->unreadCount === 0,
        );
    }

    public function test_database_notification_sent_event_broadcasts_the_private_count(): void
    {
        Event::fake([UserNotificationCountUpdated::class]);
        $this->createNotification($this->user);
        $notification = new class extends Notification {};

        app(BroadcastUserNotificationCount::class)->handle(
            new NotificationSent($this->user, $notification, 'database'),
        );

        Event::assertDispatched(
            UserNotificationCountUpdated::class,
            fn (UserNotificationCountUpdated $event): bool => $event->userUuid === $this->user->uuid
                && $event->unreadCount === 1
                && $event->broadcastWith() === [
                    'user_uuid' => $this->user->uuid,
                    'unread_count' => 1,
                    'notification_uuid' => null,
                ],
        );
    }

    public function test_authenticated_layout_receives_only_the_users_unread_notification_count(): void
    {
        $this->createNotification($this->user);
        $this->createNotification($this->user);
        $this->createNotification($this->user, now());
        $this->createNotification($this->otherUser);
        $this->actingAs($this->user);

        $view = view('layouts.app');
        app('view')->callComposer($view);

        $this->assertSame(2, $view->getData()['unreadNotificationsCount']);
    }

    public function test_user_cannot_mark_another_users_notification_as_read(): void
    {
        $notification = $this->createNotification($this->otherUser);

        $this->expectException(ModelNotFoundException::class);
        $this->service->srv_markRead($notification->id, $this->user);
    }

    public function test_authenticated_user_can_mark_own_notification_as_read_through_http(): void
    {
        $notification = $this->createNotification($this->user);

        $this->actingAs($this->user)
            ->withSession(['auth_session_version' => $this->user->session_version])
            ->from('/dashboard')
            ->patch(route('notifications.read', $notification->id))
            ->assertRedirect('/dashboard')
            ->assertSessionHas('success');

        $this->assertNotNull($notification->refresh()->read_at);
    }

    public function test_http_route_does_not_expose_another_users_notification(): void
    {
        $notification = $this->createNotification($this->otherUser);

        $this->actingAs($this->user)
            ->withSession(['auth_session_version' => $this->user->session_version])
            ->patch(route('notifications.read', $notification->id))
            ->assertNotFound();

        $this->assertNull($notification->refresh()->read_at);
    }

    public function test_user_can_fetch_their_notification_data_for_realtime_list_updates(): void
    {
        $notification = $this->createNotification($this->user);
        $notification->update(['data' => [
            'title' => '<strong>Alerte mission</strong>',
            'message' => '<script>alert(1)</script>La mission arrive à échéance.',
            'mission_uuid' => (string) Str::uuid(),
            'private_field' => 'ne doit pas être transmis',
        ]]);

        $this->actingAs($this->user)
            ->withSession(['auth_session_version' => $this->user->session_version])
            ->getJson(route('notifications.data', $notification->id))
            ->assertOk()
            ->assertJsonPath('data.id', $notification->id)
            ->assertJsonPath('data.title', 'Alerte mission')
            ->assertJsonPath('data.message', 'La mission arrive à échéance.')
            ->assertJsonMissingPath('data.private_field');

        $this->actingAs($this->otherUser)
            ->withSession(['auth_session_version' => $this->otherUser->session_version])
            ->getJson(route('notifications.data', $notification->id))
            ->assertNotFound();
    }

    public function test_notification_routes_are_registered_with_explicit_permission(): void
    {
        $expected = [
            'notifications.index' => 'GET',
            'notifications.data' => 'GET',
            'notifications.read' => 'PATCH',
            'notifications.read-all' => 'PATCH',
        ];

        foreach ($expected as $name => $method) {
            $route = Route::getRoutes()->getByName($name);

            $this->assertNotNull($route);
            $this->assertContains($method, $route->methods());
            $this->assertContains('permission:notifications_view', $route->gatherMiddleware());
        }
    }

    public function test_private_notification_channel_is_restricted_to_the_authenticated_user(): void
    {
        config([
            'broadcasting.default' => 'pusher',
            'broadcasting.connections.pusher.key' => 'testing-key',
            'broadcasting.connections.pusher.secret' => 'testing-secret',
            'broadcasting.connections.pusher.app_id' => 'testing-app',
            'broadcasting.connections.pusher.options.cluster' => 'eu',
        ]);
        require base_path('routes/channels.php');

        $payload = [
            'socket_id' => '1234.5678',
            'channel_name' => "private-user-notifications.{$this->user->uuid}",
        ];

        $this->actingAs($this->user)
            ->withSession(['auth_session_version' => $this->user->session_version])
            ->post('/broadcasting/auth', $payload)
            ->assertOk();

        $this->actingAs($this->otherUser)
            ->withSession(['auth_session_version' => $this->otherUser->session_version])
            ->post('/broadcasting/auth', $payload)
            ->assertForbidden();
    }

    public function test_layout_shows_notification_badge_with_exact_count_and_shaking_class_when_unread_notifications_exist(): void
    {
        $this->createNotification($this->user);
        $this->createNotification($this->user);

        $response = $this->actingAs($this->user)
            ->withSession(['auth_session_version' => $this->user->session_version])
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('risfm-bell-shake');
        $response->assertSee('risfm-notification-badge');
        $response->assertSee('data-user-notifications-realtime', false);
        $response->assertSee('data-user-uuid="'.$this->user->uuid.'"', false);
        $response->assertSee('2');
        $response->assertSee(route('notifications.index'));
    }

    public function test_layout_shows_99_plus_badge_when_unread_notifications_exceed_99(): void
    {
        $mock = $this->mock(UserNotificationRepositoryInterface::class);
        $mock->shouldReceive('repo_unreadCount')->andReturn(125);

        $response = $this->actingAs($this->user)
            ->withSession(['auth_session_version' => $this->user->session_version])
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('risfm-bell-shake');
        $response->assertSee('risfm-notification-badge');
        $response->assertSee('99+');
    }

    public function test_layout_hides_notification_badge_and_stops_shaking_when_all_notifications_are_read(): void
    {
        $this->createNotification($this->user, now());

        $response = $this->actingAs($this->user)
            ->withSession(['auth_session_version' => $this->user->session_version])
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertDontSee('risfm-bell-shake');
        $response->assertSee('data-notification-badge', false);
        $response->assertSee('aria-label="0 notifications non lues"', false);
        $response->assertSee('hidden', false);
        $response->assertSee(route('notifications.index'));
    }

    private function createNotification(
        UserModel $user,
        mixed $readAt = null,
    ): DatabaseNotification {
        return $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'mission_reminder',
            'data' => [
                'title' => 'Rappel de mission',
                'message' => 'Une mission nécessite votre attention.',
            ],
            'read_at' => $readAt,
        ]);
    }
}
