<?php

namespace App\Services\Administration;

use App\Contracts\Administration\UserAccountServiceInterface;
use App\Models\Administration\UserModel;
use App\Repositories\Interfaces\Administration\UserRepositoryInterface;

final class UserAccountService implements UserAccountServiceInterface
{
    public function __construct(private readonly UserRepositoryInterface $users) {}

    public function srv_findForAuthentication(string $login): ?UserModel
    {
        return $this->users->repo_findForAuthentication($login);
    }

    public function srv_findActiveByEmail(string $email): ?UserModel
    {
        return $this->users->repo_findActiveByEmail($email);
    }

    public function srv_updatePasswordHash(UserModel $user, string $passwordHash): void
    {
        $this->users->repo_updatePasswordHash($user, $passwordHash);
    }

    public function srv_markLoggedIn(UserModel $user): void
    {
        $this->users->repo_markLoggedIn($user);
    }

    public function srv_completePasswordChange(UserModel $user, #[\SensitiveParameter] string $password): void
    {
        $this->users->repo_completePasswordChange($user, $password);
    }
}
