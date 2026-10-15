<?php

namespace App\Services\Administration;

use App\Contracts\Organization\OrganizationDirectoryServiceInterface;
use App\Dto\Administration\CreateUserDTO;
use App\Dto\Administration\CreateUserResultDTO;
use App\Dto\Administration\ResetUserPasswordDTO;
use App\Dto\Administration\UpdateUserDTO;
use App\Dto\Administration\UpdateUserStatusDTO;
use App\Dto\Administration\UserFiltersDTO;
use App\Enums\Administration\PermissionCode;
use App\Enums\Administration\RoleCode;
use App\Events\Administration\UserCreated;
use App\Events\Administration\UserPasswordReset;
use App\Events\Administration\UserStatusChanged;
use App\Events\Administration\UserUpdated;
use App\Models\Administration\RoleModel;
use App\Models\Administration\UserModel;
use App\Models\Organization\ServiceModel;
use App\Repositories\Interfaces\Administration\UserRepositoryInterface;
use Illuminate\Auth\Access\AuthorizationException;
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
        private readonly OrganizationDirectoryServiceInterface $organization,
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
            'services' => $this->organization->srv_activeServices(),
        ];
    }

    /**
     * @return array{
     *     roles: Collection<int, RoleModel>,
     *     services: Collection<int, ServiceModel>
     * }
     */
    public function srv_formOptions(UserModel $actor): array
    {
        $roles = $this->users->repo_activeRoles();
        if (! $this->users->repo_isActiveSystemAdministrator($actor)) {
            $roles = $roles->reject(fn (RoleModel $role): bool => $role->role_code === RoleCode::SystemAdmin->value);
        }

        return [
            'roles' => $roles,
            'services' => $this->organization->srv_activeServices(),
        ];
    }

    /** @return array<string, mixed> */
    public function srv_editData(UserModel $user, UserModel $actor): array
    {
        $this->assertCanManageAccount($actor, $user);

        return $this->srv_formOptions($actor) + ['managedUser' => $user];
    }

    /** @throws ValidationException */
    public function srv_create(CreateUserDTO $data, UserModel $actor): CreateUserResultDTO
    {
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

        $user = DB::transaction(function () use ($resolvedData, $actor, $temporaryPassword): UserModel {
            [$lockedActor] = $this->lockManagementUsers($actor);
            [$role] = $this->assertActiveReferences($resolvedData->roleCodeUnique, $resolvedData->serviceCodeUnique);
            $this->assertCanManageAccount($lockedActor, assignedRole: $role);

            return $this->users->repo_create($resolvedData, $lockedActor, $temporaryPassword);
        }, 3);

        UserCreated::dispatch($user->uuid, $actor->uuid);

        return new CreateUserResultDTO($user->uuid, $temporaryPassword);
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
        $updatedUser = DB::transaction(function () use ($user, $data, $actor): UserModel {
            [$lockedActor, $lockedUser] = $this->lockManagementUsers($actor, $user);
            [$role] = $this->assertActiveReferences($data->roleCodeUnique, $data->serviceCodeUnique);
            $this->assertCanManageAccount($lockedActor, $lockedUser, $role);

            if ($role->role_code !== RoleCode::SystemAdmin->value
                && $this->users->repo_isActiveSystemAdministrator($lockedUser)
                && $this->users->repo_countActiveSystemAdministrators() <= 1) {
                throw ValidationException::withMessages([
                    'role_code_unique' => 'Le dernier administrateur système actif doit conserver son rôle.',
                ]);
            }

            return $this->users->repo_update($lockedUser, $data);
        }, 3);

        UserUpdated::dispatch($updatedUser->uuid, $actor->uuid);

        return $updatedUser;
    }

    /** @throws ValidationException */
    public function srv_updateStatus(
        UserModel $user,
        UpdateUserStatusDTO $data,
        UserModel $actor,
    ): UserModel {
        [$updatedUser, $changed] = DB::transaction(function () use ($user, $data, $actor): array {
            [$lockedActor, $lockedUser] = $this->lockManagementUsers($actor, $user);
            $this->assertCanManageAccount($lockedActor, $lockedUser);

            if ($lockedUser->is_active === $data->isActive) {
                return [$lockedUser, false];
            }

            if ($lockedUser->is($lockedActor) && ! $data->isActive) {
                throw ValidationException::withMessages([
                    'is_active' => 'Vous ne pouvez pas désactiver votre propre compte.',
                ]);
            }

            if (! $data->isActive
                && $this->users->repo_isActiveSystemAdministrator($lockedUser)
                && $this->users->repo_countActiveSystemAdministrators() <= 1) {
                throw ValidationException::withMessages([
                    'is_active' => 'Le dernier administrateur système actif ne peut pas être désactivé.',
                ]);
            }

            return [$this->users->repo_setActive($lockedUser, $data->isActive), true];
        }, 3);

        if (! $changed) {
            return $updatedUser;
        }

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
        $temporaryPassword = Str::password(
            length: 20,
            letters: true,
            numbers: true,
            symbols: true,
            spaces: false,
        );

        $updatedUser = DB::transaction(function () use ($user, $data, $actor, $temporaryPassword): UserModel {
            [$lockedActor, $lockedUser] = $this->lockManagementUsers($actor, $user);
            $this->assertCanManageAccount($lockedActor, $lockedUser);
            if (! Hash::check($data->currentPassword, $lockedActor->password)) {
                throw ValidationException::withMessages([
                    'current_password' => 'Votre mot de passe actuel est incorrect.',
                ]);
            }

            return $this->users->repo_resetPassword($lockedUser, $temporaryPassword);
        }, 3);

        UserPasswordReset::dispatch($updatedUser->uuid, $actor->uuid);

        return $temporaryPassword;
    }

    private function assertCanManageAccount(
        UserModel $actor,
        ?UserModel $target = null,
        ?RoleModel $assignedRole = null,
    ): void {
        if (! $actor->can(PermissionCode::UsersManage->value)) {
            throw new AuthorizationException('Vous ne pouvez pas gérer les comptes utilisateurs.');
        }

        $protectedTarget = $target !== null && $this->users->repo_hasSystemAdministratorRole($target);
        $assignsAdministrator = $assignedRole?->role_code === RoleCode::SystemAdmin->value;
        if (($protectedTarget || $assignsAdministrator)
            && ! $this->users->repo_isActiveSystemAdministrator($actor)) {
            throw new AuthorizationException('Seul un administrateur système peut créer ou modifier un compte administrateur.');
        }
    }

    /** @return array{UserModel, UserModel|null} */
    private function lockManagementUsers(UserModel $actor, ?UserModel $target = null): array
    {
        $this->users->repo_lockActiveSystemAdministrators();
        $codes = [$actor->user_code_unique];
        if ($target !== null) {
            $codes[] = $target->user_code_unique;
        }
        $lockedUsers = $this->users->repo_lockForManagement(array_values(array_unique($codes)));
        $lockedActor = $lockedUsers->get($actor->user_code_unique);
        $lockedTarget = $target !== null ? $lockedUsers->get($target->user_code_unique) : null;

        if (! $lockedActor instanceof UserModel || ($target !== null && ! $lockedTarget instanceof UserModel)) {
            throw new AuthorizationException('Le compte utilisateur est indisponible.');
        }

        return [$lockedActor, $lockedTarget];
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

        $service = $this->organization->srv_findActiveService($serviceCodeUnique);

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
