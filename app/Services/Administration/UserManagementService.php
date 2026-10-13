<?php

namespace App\Services\Administration;

use App\Dto\Administration\CreateUserDTO;
use App\Dto\Administration\CreateUserResultDTO;
use App\Dto\Administration\ResetUserPasswordDTO;
use App\Dto\Administration\UpdateUserDTO;
use App\Dto\Administration\UpdateUserStatusDTO;
use App\Dto\Administration\UserFiltersDTO;
use App\Enums\Administration\RoleCode;
use App\Events\Administration\UserCreated;
use App\Events\Administration\UserPasswordReset;
use App\Events\Administration\UserStatusChanged;
use App\Events\Administration\UserUpdated;
use App\Models\Administration\RoleModel;
use App\Models\Administration\UserModel;
use App\Models\Organization\ServiceModel;
use App\Repositories\Interfaces\Administration\UserRepositoryInterface;
use App\Repositories\Interfaces\Organization\OrganizationDirectoryRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class UserManagementService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly OrganizationDirectoryRepositoryInterface $organization,
    ) {}

    /**
     * @return array{
     *     users: LengthAwarePaginator<int, UserModel>,
     *     roles: Collection<int, RoleModel>,
     *     services: Collection<int, ServiceModel>
     * }
     */
    public function srv_list(UserFiltersDTO $filters): array
    {
        return [
            'users' => $this->users->repo_paginate($filters),
            'roles' => $this->users->repo_activeRoles(),
            'services' => $this->organization->repo_activeServices(),
        ];
    }

    /**
     * @return array{
     *     roles: Collection<int, RoleModel>,
     *     services: Collection<int, ServiceModel>
     * }
     */
    public function srv_formOptions(): array
    {
        return [
            'roles' => $this->users->repo_activeRoles(),
            'services' => $this->organization->repo_activeServices(),
        ];
    }

    /** @throws ValidationException */
    public function srv_create(CreateUserDTO $data, UserModel $actor): CreateUserResultDTO
    {
        $this->assertActiveReferences($data->roleCodeUnique, $data->serviceCodeUnique);

        $resolvedData = $data->username === null
            ? $data->withUsername($this->srv_generateUsername($data->firstNames, $data->lastName))
            : $data;
        $temporaryPassword = Str::password(
            length: 20,
            letters: true,
            numbers: true,
            symbols: true,
            spaces: false,
        );

        $user = DB::transaction(
            fn (): UserModel => $this->users->repo_create(
                $resolvedData,
                $actor,
                $temporaryPassword,
            ),
        );

        UserCreated::dispatch($user->uuid, $actor->uuid);

        return new CreateUserResultDTO($user, $temporaryPassword);
    }

    public function srv_generateUsername(string $firstNames, string $lastName): string
    {
        $firstName = preg_split('/\s+/u', trim($firstNames), 2)[0] ?? '';
        $firstLastName = preg_split('/\s+/u', trim($lastName), 2)[0] ?? '';
        $firstNameSegment = $this->normalizeUsernameSegment($firstName);
        $lastNameSegment = $this->normalizeUsernameSegment($firstLastName);
        $base = trim($firstNameSegment.'.'.$lastNameSegment, '.-');
        $base = mb_substr($base, 0, 50);

        if ($base === '' || mb_strlen($base) < 3) {
            throw ValidationException::withMessages([
                'username' => 'Le nom et le prénom ne permettent pas de générer un identifiant valide.',
            ]);
        }

        if (! $this->users->repo_usernameExists($base)) {
            return $base;
        }

        for ($sequence = 2; ; $sequence++) {
            $suffix = (string) $sequence;
            $stem = rtrim(mb_substr($base, 0, 50 - mb_strlen($suffix)), '.-');
            $candidate = $stem.$suffix;

            if (! $this->users->repo_usernameExists($candidate)) {
                return $candidate;
            }
        }
    }

    /** @throws ValidationException */
    public function srv_update(
        UserModel $user,
        UpdateUserDTO $data,
        UserModel $actor,
    ): UserModel {
        [$role] = $this->assertActiveReferences(
            $data->roleCodeUnique,
            $data->serviceCodeUnique,
        );

        if (
            $role->role_code !== RoleCode::SystemAdmin->value
            && $this->users->repo_isActiveSystemAdministrator($user)
            && $this->users->repo_countActiveSystemAdministrators() <= 1
        ) {
            throw ValidationException::withMessages([
                'role_code_unique' => 'Le dernier administrateur système actif doit conserver son rôle.',
            ]);
        }

        $updatedUser = DB::transaction(
            fn (): UserModel => $this->users->repo_update($user, $data),
        );

        UserUpdated::dispatch($updatedUser->uuid, $actor->uuid);

        return $updatedUser;
    }

    /** @throws ValidationException */
    public function srv_updateStatus(
        UserModel $user,
        UpdateUserStatusDTO $data,
        UserModel $actor,
    ): UserModel {
        if ($user->is_active === $data->isActive) {
            return $user;
        }

        if ($user->is($actor) && ! $data->isActive) {
            throw ValidationException::withMessages([
                'is_active' => 'Vous ne pouvez pas désactiver votre propre compte.',
            ]);
        }

        if (
            ! $data->isActive
            && $this->users->repo_isActiveSystemAdministrator($user)
            && $this->users->repo_countActiveSystemAdministrators() <= 1
        ) {
            throw ValidationException::withMessages([
                'is_active' => 'Le dernier administrateur système actif ne peut pas être désactivé.',
            ]);
        }

        $updatedUser = DB::transaction(
            fn (): UserModel => $this->users->repo_setActive($user, $data->isActive),
        );

        UserStatusChanged::dispatch(
            $updatedUser->uuid,
            $actor->uuid,
            $updatedUser->is_active,
        );

        return $updatedUser;
    }

    /** @throws ValidationException */
    public function srv_resetPassword(
        UserModel $user,
        ResetUserPasswordDTO $data,
        UserModel $actor,
    ): string {
        if (! Hash::check($data->currentPassword, $actor->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'Votre mot de passe actuel est incorrect.',
            ]);
        }

        $temporaryPassword = Str::password(
            length: 20,
            letters: true,
            numbers: true,
            symbols: true,
            spaces: false,
        );

        $updatedUser = DB::transaction(
            fn (): UserModel => $this->users->repo_resetPassword($user, $temporaryPassword),
        );

        UserPasswordReset::dispatch($updatedUser->uuid, $actor->uuid);

        return $temporaryPassword;
    }

    /**
     * @return array{RoleModel, ServiceModel}
     *
     * @throws ValidationException
     */
    private function assertActiveReferences(
        string $roleCodeUnique,
        string $serviceCodeUnique,
    ): array {
        $role = $this->users->repo_findActiveRole($roleCodeUnique);

        if (! $role instanceof RoleModel) {
            throw ValidationException::withMessages([
                'role_code_unique' => 'Le rôle sélectionné est indisponible.',
            ]);
        }

        $service = $this->organization->repo_findActiveService($serviceCodeUnique);

        if (! $service instanceof ServiceModel) {
            throw ValidationException::withMessages([
                'service_code_unique' => 'Le service sélectionné est indisponible.',
            ]);
        }

        return [$role, $service];
    }

    private function normalizeUsernameSegment(string $value): string
    {
        $asciiValue = Str::lower(Str::ascii(trim($value)));
        $normalized = preg_replace('/[^a-z0-9]+/', '-', $asciiValue) ?? '';

        return trim($normalized, '-');
    }
}
