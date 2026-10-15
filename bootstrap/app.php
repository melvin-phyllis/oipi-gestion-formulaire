<?php

use App\Http\Middleware\AddContentSecurityPolicy;
use App\Http\Middleware\EnsurePasswordChanged;
use App\Http\Middleware\EnsurePermission;
use App\Http\Middleware\EnsureSessionIsCurrent;
use App\Http\Middleware\PreventFraming;
use App\Http\Middleware\PreventSearchEngineIndexing;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: [
            __DIR__.'/../routes/web.php',
            __DIR__.'/../routes/authentication.php',
            __DIR__.'/../routes/administration.php',
            __DIR__.'/../routes/organization.php',
            __DIR__.'/../routes/registry.php',
            __DIR__.'/../routes/missions.php',
            __DIR__.'/../routes/notifications.php',
            __DIR__.'/../routes/analytics.php',
            __DIR__.'/../routes/audit.php',
            __DIR__.'/../routes/backups.php',
        ],
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withBroadcasting(
        __DIR__.'/../routes/channels.php',
        ['middleware' => ['web', 'auth', 'session.current', 'password.changed']],
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend([AddContentSecurityPolicy::class, PreventFraming::class]);
        $middleware->trustHosts(
            at: fn (): array => [
                '^'.preg_quote((string) parse_url((string) config('app.url'), PHP_URL_HOST)).'$',
            ],
            subdomains: false,
        );
        $middleware->append(PreventSearchEngineIndexing::class);
        $middleware->redirectGuestsTo(fn (Request $request): string => route('login'));
        $middleware->redirectUsersTo(fn (Request $request): string => route('dashboard'));
        $middleware->alias([
            'permission' => EnsurePermission::class,
            'password.changed' => EnsurePasswordChanged::class,
            'session.current' => EnsureSessionIsCurrent::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (ThrottleRequestsException $exception, Request $request): ?JsonResponse {
            if (! $request->expectsJson()) {
                return null;
            }

            $headers = $exception->getHeaders();
            $retryAfter = max(1, (int) ($headers['Retry-After'] ?? 60));

            return response()->json([
                'message' => "Trop de tentatives. Réessayez dans {$retryAfter} seconde(s).",
                'retry_after' => $retryAfter,
            ], 429, $headers + ['Cache-Control' => 'no-store, private']);
        });
    })->create();
