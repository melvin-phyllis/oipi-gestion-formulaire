<?php

namespace App\Http\Middleware;

use App\Models\Administration\UserModel;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class EnsureSessionIsCurrent
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $sessionVersion = $request->session()->get('auth_session_version');

        if (
            ! $user instanceof UserModel
            || ! is_int($sessionVersion)
            || $sessionVersion !== $user->session_version
        ) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors([
                    'login' => 'Votre session a expiré. Veuillez vous reconnecter.',
                ]);
        }

        return $next($request);
    }
}
