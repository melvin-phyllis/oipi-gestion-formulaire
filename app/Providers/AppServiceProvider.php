<?php

namespace App\Providers;

use App\Contracts\Administration\UserAccountServiceInterface;
use App\Contracts\Administration\UserDirectoryServiceInterface;
use App\Contracts\Audit\AuditActivityReadServiceInterface;
use App\Contracts\Audit\AuditContextProviderInterface;
use App\Contracts\Organization\OrganizationDirectoryServiceInterface;
use App\Contracts\Registry\RegistryReferenceReadServiceInterface;
use App\Enums\Administration\PermissionCode;
use App\Http\Audit\HttpAuditContextProvider;
use App\Listeners\Administration\BroadcastUserNotificationCount;
use App\Listeners\Audit\AuditEventSubscriber;
use App\Listeners\Mission\MissionNotificationSubscriber;
use App\Models\Administration\UserModel;
use App\Services\Administration\AuthorizationService;
use App\Services\Administration\UserAccountService;
use App\Services\Administration\UserDirectoryService;
use App\Services\Administration\UserNotificationService;
use App\Services\Audit\AuditActivityReadService;
use App\Services\Organization\OrganizationDirectoryService;
use App\Services\Registry\RegistryReferenceReadService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Cache\RateLimiting\Unlimited;
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
        $this->app->bind(AuditContextProviderInterface::class, HttpAuditContextProvider::class);
        $this->app->bind(UserDirectoryServiceInterface::class, UserDirectoryService::class);
        $this->app->bind(UserAccountServiceInterface::class, UserAccountService::class);
        $this->app->bind(OrganizationDirectoryServiceInterface::class, OrganizationDirectoryService::class);
        $this->app->bind(RegistryReferenceReadServiceInterface::class, RegistryReferenceReadService::class);
        $this->app->bind(AuditActivityReadServiceInterface::class, AuditActivityReadService::class);
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

        RateLimiter::for('login', function (Request $request): array {
            return [
                Limit::perMinute(20)->by('ip:'.$request->ip()),
                Limit::perMinute(5)->by('account:'.$this->authenticationRateLimitKey($request->input('login'))),
            ];
        });

        RateLimiter::for('password-reset', function (Request $request): array {
            return [
                Limit::perMinute(10)->by('ip:'.$request->ip()),
                Limit::perMinute(3)->by('account:'.$this->authenticationRateLimitKey($request->input('email'))),
            ];
        });

        RateLimiter::for('sensitive-actions', function (Request $request): array|Unlimited {
            $user = $request->user();
            $email = $request->input('email');

            if ($request->routeIs('profile.update') && $user instanceof UserModel
                && is_string($email) && Str::lower(trim($email)) === $user->email) {
                return Limit::none();
            }

            return [
                Limit::perMinute(20)->by('ip:'.$request->ip()),
                Limit::perMinute(5)->by('actor:'.($user?->uuid ?? 'guest')),
            ];
        });

        RateLimiter::for('attachments', fn (Request $request): Limit => Limit::perMinute(30)
            ->by(($request->user()?->uuid ?? 'guest').'|'.$request->ip()));

        RateLimiter::for('registry-imports', fn (Request $request): Limit => Limit::perMinute(5)
            ->by(($request->user()?->uuid ?? 'guest').'|'.$request->ip()));

        RateLimiter::for('profile-updates', fn (Request $request): Limit => Limit::perMinute(10)
            ->by(($request->user()?->uuid ?? 'guest').'|'.$request->ip()));
    }

    private function authenticationRateLimitKey(mixed $identifier): string
    {
        if (! is_string($identifier) || mb_strlen(trim($identifier)) > 150) {
            return 'invalid';
        }

        $identifier = trim($identifier);
        $user = app(UserAccountServiceInterface::class)->srv_findForAuthentication($identifier);
        $identity = $user instanceof UserModel
            ? 'user:'.$user->uuid
            : 'identifier:'.Str::lower(Str::ascii($identifier));

        return hash('sha256', $identity);
    }
}
