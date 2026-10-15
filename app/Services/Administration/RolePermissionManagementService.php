<?php

namespace App\Services\Administration;

use App\Dto\Administration\UpdateRolePermissionsDTO;
use App\Enums\Administration\PermissionCode;
use App\Enums\Administration\RoleCode;
use App\Events\Administration\RolePermissionsUpdated;
use App\Models\Administration\PermissionModel;
use App\Models\Administration\RoleModel;
use App\Models\Administration\UserModel;
use App\Repositories\Interfaces\Administration\RolePermissionRepositoryInterface;
use App\Repositories\Interfaces\Administration\UserRepositoryInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class RolePermissionManagementService
{
    public function __construct(
        private readonly RolePermissionRepositoryInterface $rolePermissions,
        private readonly UserRepositoryInterface $users,
    ) {}

    /**
     * @return array{
     *     roles: Collection<int, RoleModel>,
     *     permissionGroups: SupportCollection<string, Collection<int, PermissionModel>>,
     *     groupLabels: array<string, string>
     * }
     */
    public function srv_index(): array
    {
        return [
            'roles' => $this->rolePermissions->repo_activeRolesWithPermissions(),
            'permissionGroups' => $this->rolePermissions
                ->repo_activePermissions()
                ->groupBy('group_code'),
            'groupLabels' => [
                'dashboard' => 'Tableau de bord',
                'registry' => 'Registre',
                'missions' => 'Missions',
                'attachments' => 'Pièces jointes',
                'analytics' => 'Statistiques',
                'administration' => 'Administration',
                'audit' => 'Journal d’audit',
                'backups' => 'Sauvegardes',
                'notifications' => 'Notifications',
                'profile' => 'Profil personnel',
            ],
        ];
    }

    /** @throws ValidationException */
    public function srv_update(
        RoleModel $role,
        UpdateRolePermissionsDTO $data,
        UserModel $actor,
    ): RoleModel {
        [$updatedRole, $addedPermissions, $removedPermissions] = DB::transaction(
            function () use ($role, $data, $actor): array {
                $lockedActor = $this->users->repo_lockForManagement([$actor->user_code_unique])
                    ->get($actor->user_code_unique);
                if (! $lockedActor instanceof UserModel || ! $lockedActor->can(PermissionCode::RolesManage->value)) {
                    throw new AuthorizationException('Seul un administrateur système peut modifier les rôles et permissions.');
                }

                $lockedRole = $this->rolePermissions
                    ->repo_findRoleForUpdateByUuid($role->uuid);

                if ($lockedRole->role_code === RoleCode::SystemAdmin->value) {
                    throw ValidationException::withMessages([
                        'permissions' => 'Les permissions de l’administrateur système sont protégées.',
                    ]);
                }

                if (in_array(PermissionCode::RolesManage->value, $data->permissionCodes, true)) {
                    throw ValidationException::withMessages([
                        'permissions' => 'La gestion des rôles et permissions est réservée aux administrateurs système.',
                    ]);
                }

                if (in_array(PermissionCode::AuditView->value, $data->permissionCodes, true)) {
                    throw ValidationException::withMessages([
                        'permissions' => 'La consultation du journal d’audit est réservée aux administrateurs système.',
                    ]);
                }

                $before = $this->rolePermissions
                    ->repo_activePermissionCodes($lockedRole);
                $updatedRole = $this->rolePermissions
                    ->repo_syncActivePermissions($lockedRole, $data->permissionCodes);
                $after = $this->rolePermissions
                    ->repo_activePermissionCodes($updatedRole);

                return [
                    $updatedRole,
                    array_values(array_diff($after, $before)),
                    array_values(array_diff($before, $after)),
                ];
            },
            3,
        );

        RolePermissionsUpdated::dispatch(
            $updatedRole->uuid,
            $actor->uuid,
            $addedPermissions,
            $removedPermissions,
        );

        return $updatedRole;
    }
}
