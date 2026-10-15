<?php

namespace App\Contracts\Administration;

use App\Models\Administration\UserModel;

interface UserAccountServiceInterface
{
    public function srv_findForAuthentication(string $login): ?UserModel;

    public function srv_findActiveByEmail(string $email): ?UserModel;

    public function srv_updatePasswordHash(UserModel $user, string $passwordHash): void;

    public function srv_markLoggedIn(UserModel $user): void;

    public function srv_completePasswordChange(UserModel $user, #[\SensitiveParameter] string $password): void;
}
