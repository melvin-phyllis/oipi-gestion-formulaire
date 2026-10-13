<?php

namespace App\Providers;

use App\Enums\Administration\PermissionCode;
use App\Listeners\Administration\BroadcastUserNotificationCount;
use App\Listeners\Audit\AuditEventSubscriber;
use App\Listeners\Mission\MissionNotificationSubscriber;
use App\Models\Administration\UserModel;
use App\Services\Administration\AuthorizationService;
use App\Services\Administration\UserNotificationService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Http\Request;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        Event::subscribe(AuditEventSubscriber::class);
        Event::subscribe(MissionNotificationSubscriber::class);
        Event::listen(NotificationSent::class, BroadcastUserNotificationCount::class);

        foreach (PermissionCode::cases() as $permission) {
            Gate::define(
                $permission->value,
                fn (UserModel $user): bool => app(AuthorizationService::class)
                    ->srv_userHasPermission($user, $permission),
            );
        }

        View::composer('layouts.app', function (ViewContract $view): void {
            $user = Auth::user();
            $unreadNotificationsCount = 0;

            if ($user instanceof UserModel && Gate::allows(PermissionCode::NotificationsView->value)) {
                $unreadNotificationsCount = app(UserNotificationService::class)
                    ->srv_unreadCount($user);
            }

            $view->with('unreadNotificationsCount', $unreadNotificationsCount);
        });

        RateLimiter::for('login', function (Request $request): Limit {
            $login = Str::lower(trim((string) $request->input('login')));

            return Limit::perMinute(5)->by($login.'|'.$request->ip());
        });

        RateLimiter::for('password-reset', function (Request $request): Limit {
            $email = Str::lower(trim((string) $request->input('email')));

            return Limit::perMinute(3)->by($email.'|'.$request->ip());
        });

        RateLimiter::for('attachments', fn (Request $request): Limit => Limit::perMinute(30)
            ->by(($request->user()?->uuid ?? 'guest').'|'.$request->ip()));

        RateLimiter::for('registry-imports', fn (Request $request): Limit => Limit::perMinute(5)
            ->by(($request->user()?->uuid ?? 'guest').'|'.$request->ip()));

        RateLimiter::for('profile-updates', fn (Request $request): Limit => Limit::perMinute(10)
            ->by(($request->user()?->uuid ?? 'guest').'|'.$request->ip()));
    }
}
