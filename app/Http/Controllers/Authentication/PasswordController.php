<?php

namespace App\Http\Controllers\Authentication;

use App\Dto\Authentication\ChangePasswordDTO;
use App\Dto\Authentication\ForgotPasswordDTO;
use App\Dto\Authentication\ResetPasswordDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Authentication\ChangePasswordFormRequest;
use App\Http\Requests\Authentication\ForgotPasswordFormRequest;
use App\Http\Requests\Authentication\ResetPasswordFormRequest;
use App\Models\Administration\UserModel;
use App\Services\Authentication\PasswordService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class PasswordController extends Controller
{
    public function __construct(
        private readonly PasswordService $passwords,
    ) {}

    public function ctrl_showChange(): View
    {
        return view('authentication.change-password');
    }

    public function ctrl_change(ChangePasswordFormRequest $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof UserModel, 403);

        $this->passwords->srv_changePassword(
            $user,
            ChangePasswordDTO::fromValidated($request->validated()),
        );

        $request->session()->regenerate();
        $request->session()->put('auth_session_version', $user->session_version);

        return redirect()
            ->route('dashboard')
            ->with('success', 'Votre mot de passe a été modifié.');
    }

    public function ctrl_showForgot(): View
    {
        return view('authentication.forgot-password');
    }

    public function ctrl_sendResetLink(ForgotPasswordFormRequest $request): RedirectResponse
    {
        $this->passwords->srv_sendResetLink(
            ForgotPasswordDTO::fromValidated($request->validated()),
        );

        return back()->with(
            'success',
            'Si cette adresse correspond à un compte actif, un lien de réinitialisation a été envoyé.',
        );
    }

    public function ctrl_showReset(Request $request, string $token): View
    {
        return view('authentication.reset-password', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function ctrl_reset(ResetPasswordFormRequest $request): RedirectResponse
    {
        $this->passwords->srv_resetPassword(
            ResetPasswordDTO::fromValidated($request->validated()),
        );

        return redirect()
            ->route('login')
            ->with('success', 'Votre mot de passe a été réinitialisé. Vous pouvez maintenant vous connecter.');
    }
}
