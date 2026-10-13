<?php

namespace App\Http\Controllers\Authentication;

use App\Dto\Authentication\LoginDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Authentication\LoginFormRequest;
use App\Services\Authentication\AuthenticationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class AuthController extends Controller
{
    public function __construct(
        private readonly AuthenticationService $authentication,
    ) {}

    public function ctrl_showLogin(): View
    {
        return view('authentication.login');
    }

    public function ctrl_login(LoginFormRequest $request): RedirectResponse
    {
        $credentials = LoginDTO::fromValidated($request->validated());
        $user = $this->authentication->srv_authenticate($credentials);

        Auth::login($user, $credentials->remember);
        $request->session()->regenerate();
        $request->session()->put('auth_session_version', $user->session_version);

        if ($user->must_change_password) {
            return redirect()->route('password.change');
        }

        return redirect()->intended(route('dashboard'));
    }

    public function ctrl_logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('success', 'Vous êtes maintenant déconnecté.');
    }
}
