<?php

use App\Http\Middleware\EnsurePasswordChanged;
use App\Http\Middleware\EnsurePermission;
use App\Http\Middleware\EnsureSessionIsCurrent;
use App\Http\Middleware\PreventSearchEngineIndexing;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
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
    })->create();
