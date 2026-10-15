<?php

namespace App\Services\Authentication;

use App\Contracts\Administration\UserAccountServiceInterface;
use App\Dto\Authentication\LoginDTO;
use App\Events\Authentication\AuthenticationFailed;
use App\Models\Administration\UserModel;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class AuthenticationService
{
    public function __construct(
        private readonly UserAccountServiceInterface $users,
    ) {}

    /** @throws ValidationException */
    public function srv_authenticate(LoginDTO $credentials): UserModel
    {
        $user = $this->users->srv_findForAuthentication($credentials->login);

        if (
            ! $user instanceof UserModel
            || ! $user->is_active
            || ! $user->role?->is_active
            || ! Hash::check($credentials->password, $user->password)
        ) {
            AuthenticationFailed::dispatch(hash_hmac(
                'sha256',
                Str::lower(trim($credentials->login)),
                (string) config('app.key'),
            ));

            throw ValidationException::withMessages([
                'login' => 'Identifiant ou mot de passe incorrect.',
            ]);
        }

        if (Hash::needsRehash($user->password)) {
            $this->users->srv_updatePasswordHash(
                $user,
                Hash::make($credentials->password),
            );
        }

        $this->users->srv_markLoggedIn($user);

        return $user;
    }
}
