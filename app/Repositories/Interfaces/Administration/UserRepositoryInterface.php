<?php

namespace App\Repositories\Interfaces\Administration;

use App\Dto\Administration\CreateUserDTO;
use App\Dto\Administration\UpdateProfileDTO;
use App\Dto\Administration\UpdateUserDTO;
use App\Dto\Administration\UserFiltersDTO;
use App\Models\Administration\RoleModel;
use App\Models\Administration\UserModel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface UserRepositoryInterface
{
    /** @return LengthAwarePaginator<int, UserModel> */
    public function repo_paginate(UserFiltersDTO $filters, int $perPage = 15): LengthAwarePaginator;

    /** @return Collection<int, RoleModel> */
    public function repo_activeRoles(): Collection;

    /** @return Collection<int, UserModel> */
    public function repo_activeUsers(): Collection;

    public function repo_findActiveRole(string $roleCodeUnique): ?RoleModel;

    public function repo_usernameExists(string $username): bool;

    public function repo_create(
        CreateUserDTO $data,
        UserModel $actor,
        #[\SensitiveParameter] string $temporaryPassword,
    ): UserModel;

    public function repo_update(UserModel $user, UpdateUserDTO $data): UserModel;

    public function repo_updateProfile(UserModel $user, UpdateProfileDTO $data): UserModel;

    public function repo_setActive(UserModel $user, bool $isActive): UserModel;

    public function repo_resetPassword(UserModel $user, string $password): UserModel;

    public function repo_isActiveSystemAdministrator(UserModel $user): bool;

    public function repo_countActiveSystemAdministrators(): int;

    public function repo_findForAuthentication(string $login): ?UserModel;

    public function repo_findActiveByEmail(string $email): ?UserModel;

    public function repo_findByCodeUnique(string $codeUnique): UserModel;

    public function repo_updatePasswordHash(UserModel $user, string $passwordHash): void;

    public function repo_markLoggedIn(UserModel $user): void;

    public function repo_completePasswordChange(UserModel $user, string $password): void;
}
