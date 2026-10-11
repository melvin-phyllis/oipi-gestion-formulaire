# Web route pattern

Use this reference when adding or changing routes, middleware, route model binding, or permissions.

## Files

Group web routes by domain:

```text
routes/authentication.php
routes/administration.php
routes/organization.php
routes/registry.php
routes/mission.php
routes/analytics.php
routes/audit.php
routes/backup.php
```

Load them from `bootstrap/app.php`. They remain in the `web` middleware group and use session authentication and CSRF protection.

```php
Route::middleware(['auth', 'permission:missions_assign'])
    ->prefix('missions')
    ->name('missions.')
    ->group(function (): void {
        Route::post('{missionUuid}/assign', [MissionController::class, 'ctrl_assign'])
            ->whereUuid('missionUuid')
            ->name('assign');
    });
```

## Authorization rules

- Every protected route declares authentication and its explicit permission or policy boundary.
- Permission codes use lowercase `resource_action` form, such as `missions_assign`; they never contain dots.
- Unknown permission codes and missing mappings deny access.
- Never derive permission codes from controller names or method names.
- Re-check resource ownership and state through a policy or service where route-level permission alone is insufficient.
- Apply rate limiting to login, password recovery, imports, exports, backup, restore, and other abuse-sensitive endpoints.
- Sensitive actions may additionally require password confirmation middleware.

Routes, parameters, names, status values, and permission codes stay in English. Laravel route names may use dots, while permission codes use underscores. Visible navigation labels remain French.
