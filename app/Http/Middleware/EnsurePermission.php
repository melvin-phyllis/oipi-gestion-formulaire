<?php

namespace App\Http\Middleware;

use App\Enums\Administration\PermissionCode;
use App\Models\Administration\UserModel;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsurePermission
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(
        Request $request,
        Closure $next,
        string ...$permissionCodes,
    ): Response {
        $user = $request->user();

        abort_unless(
            $user instanceof UserModel
            && collect($permissionCodes)->contains(function (string $permissionCode) use ($user): bool {
                $permission = PermissionCode::tryFrom($permissionCode);

                return $permission instanceof PermissionCode && $user->can($permission->value);
            }),
            403,
        );

        return $next($request);
    }
}
