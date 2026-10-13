<?php

namespace App\Http\Middleware;

use App\Models\Administration\UserModel;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsurePasswordChanged
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof UserModel && $user->must_change_password) {
            return redirect()->route('password.change');
        }

        return $next($request);
    }
}
