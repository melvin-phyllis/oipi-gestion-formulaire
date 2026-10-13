<?php

namespace App\Repositories\Administration;

use App\Dto\Administration\CreateUserDTO;
use App\Dto\Administration\UpdateProfileDTO;
use App\Dto\Administration\UpdateUserDTO;
use App\Dto\Administration\UserFiltersDTO;
use App\Enums\Administration\RoleCode;
use App\Models\Administration\RoleModel;
use App\Models\Administration\UserModel;
use App\Repositories\Interfaces\Administration\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

final class UserRepository implements UserRepositoryInterface
{
    public function repo_paginate(
        UserFiltersDTO $filters,
        int $perPage = 15,
    ): LengthAwarePaginator {
        return UserModel::query()
            ->with(['role', 'service.directorate'])
            ->when($filters->search, function (Builder $query, string $search): void {
                $query->where(function (Builder $nestedQuery) use ($search): void {
                    $nestedQuery
                        ->where('username', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('first_names', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when(
                $filters->roleCodeUnique,
                fn (Builder $query, string $roleCode): Builder => $query
                    ->where('role_code_unique', $roleCode),
            )
            ->when(
                $filters->serviceCodeUnique,
                fn (Builder $query, string $serviceCode): Builder => $query
                    ->where('service_code_unique', $serviceCode),
            )
            ->when(
                $filters->isActive !== null,
                fn (Builder $query): Builder => $query
                    ->where('is_active', $filters->isActive),
            )
            ->orderBy('last_name')
            ->orderBy('first_names')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function repo_activeRoles(): Collection
    {
        return RoleModel::query()
            ->where('is_active', true)
            ->orderBy('role_name')
            ->get();
    }

    public function repo_activeUsers(): Collection
    {
        return UserModel::query()
            ->with(['role', 'service.directorate'])
            ->where('is_active', true)
            ->orderBy('last_name')
            ->orderBy('first_names')
            ->get();
    }

    public function repo_findActiveRole(string $roleCodeUnique): ?RoleModel
    {
        return RoleModel::query()
            ->where('role_code_unique', $roleCodeUnique)
            ->where('is_active', true)
            ->first();
    }

    public function repo_usernameExists(string $username): bool
    {
        return UserModel::query()
            ->withTrashed()
            ->where('username', $username)
            ->exists();
    }

    public function repo_create(
        CreateUserDTO $data,
        UserModel $actor,
        #[\SensitiveParameter] string $temporaryPassword,
    ): UserModel {
        $user = UserModel::query()->create([
            'role_code_unique' => $data->roleCodeUnique,
            'service_code_unique' => $data->serviceCodeUnique,
            'username' => $data->username,
            'last_name' => $data->lastName,
            'first_names' => $data->firstNames,
            'email' => $data->email,
            'phone' => $data->phone,
            'password' => $temporaryPassword,
            'is_active' => true,
            'must_change_password' => true,
            'session_version' => 1,
            'created_by_code_unique' => $actor->user_code_unique,
        ]);

        return $user->load(['role', 'service.directorate']);
    }

    public function repo_update(UserModel $user, UpdateUserDTO $data): UserModel
    {
        $user->fill([
            'role_code_unique' => $data->roleCodeUnique,
            'service_code_unique' => $data->serviceCodeUnique,
            'username' => $data->username,
            'last_name' => $data->lastName,
            'first_names' => $data->firstNames,
            'email' => $data->email,
            'phone' => $data->phone,
        ]);
        $user->save();

        return $user->refresh()->load(['role', 'service.directorate']);
    }

    public function repo_updateProfile(UserModel $user, UpdateProfileDTO $data): UserModel
    {
        $user->fill([
            'last_name' => $data->lastName,
            'first_names' => $data->firstNames,
            'email' => $data->email,
            'phone' => $data->phone,
        ]);
        $user->save();

        return $user->refresh()->load(['role', 'service.directorate']);
    }

    public function repo_setActive(UserModel $user, bool $isActive): UserModel
    {
        if ($user->is_active !== $isActive) {
            $user->is_active = $isActive;
            $user->session_version++;
            $user->save();
        }

        return $user->refresh();
    }

    public function repo_resetPassword(UserModel $user, string $password): UserModel
    {
        $user->password = $password;
        $user->must_change_password = true;
        $user->session_version++;
        $user->setRememberToken(Str::random(60));
        $user->save();

        return $user->refresh();
    }

    public function repo_isActiveSystemAdministrator(UserModel $user): bool
    {
        return $user->is_active
            && RoleModel::query()
                ->where('role_code_unique', $user->role_code_unique)
                ->where('role_code', RoleCode::SystemAdmin->value)
                ->exists();
    }

    public function repo_countActiveSystemAdministrators(): int
    {
        return UserModel::query()
            ->where('is_active', true)
            ->whereHas('role', fn (Builder $query): Builder => $query
                ->where('role_code', RoleCode::SystemAdmin->value))
            ->count();
    }

    public function repo_findForAuthentication(string $login): ?UserModel
    {
        return UserModel::query()
            ->with('role')
            ->where(function ($query) use ($login): void {
                $query
                    ->where('username', $login)
                    ->orWhere('email', $login);
            })
            ->first();
    }

    public function repo_updatePasswordHash(UserModel $user, string $passwordHash): void
    {
        $user->password = $passwordHash;
        $user->save();
    }

    public function repo_findActiveByEmail(string $email): ?UserModel
    {
        return UserModel::query()
            ->where('email', $email)
            ->where('is_active', true)
            ->first();
    }

    public function repo_findByCodeUnique(string $codeUnique): UserModel
    {
        return UserModel::withTrashed()
            ->where('user_code_unique', $codeUnique)
            ->firstOrFail();
    }

    public function repo_markLoggedIn(UserModel $user): void
    {
        $user->last_login_at = now();
        $user->save();
    }

    public function repo_completePasswordChange(UserModel $user, string $password): void
    {
        $user->password = $password;
        $user->must_change_password = false;
        $user->session_version++;
        $user->setRememberToken(Str::random(60));
        $user->save();
    }
}
