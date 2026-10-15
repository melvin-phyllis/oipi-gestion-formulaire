<?php

namespace App\Services\Authentication;

use App\Contracts\Administration\UserAccountServiceInterface;
use App\Dto\Authentication\ChangePasswordDTO;
use App\Dto\Authentication\ForgotPasswordDTO;
use App\Dto\Authentication\ResetPasswordDTO;
use App\Events\Authentication\UserPasswordChanged;
use App\Models\Administration\UserModel;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

final class PasswordService
{
    public function __construct(
        private readonly UserAccountServiceInterface $users,
    ) {}

    /** @throws ValidationException */
    public function srv_changePassword(
        UserModel $user,
        ChangePasswordDTO $data,
    ): void {
        if (! Hash::check($data->currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'Le mot de passe actuel est incorrect.',
            ]);
        }

        $this->users->srv_completePasswordChange($user, $data->password);
        UserPasswordChanged::dispatch($user->uuid, 'self_change');
    }

    public function srv_sendResetLink(ForgotPasswordDTO $data): void
    {
        $user = $this->users->srv_findActiveByEmail($data->email);

        if (! $user instanceof UserModel) {
            return;
        }

        Password::sendResetLink(['email' => $data->email]);
    }

    /** @throws ValidationException */
    public function srv_resetPassword(ResetPasswordDTO $data): void
    {
        $userUuid = null;
        $status = Password::reset(
            [
                'email' => $data->email,
                'token' => $data->token,
                'password' => $data->password,
                'password_confirmation' => $data->password,
                'is_active' => true,
            ],
            function (UserModel $user, string $password) use (&$userUuid): void {
                $this->users->srv_completePasswordChange($user, $password);
                $userUuid = $user->uuid;
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => 'Ce lien de réinitialisation est invalide ou a expiré.',
            ]);
        }

        if (is_string($userUuid)) {
            UserPasswordChanged::dispatch($userUuid, 'reset_link');
        }
    }
}
