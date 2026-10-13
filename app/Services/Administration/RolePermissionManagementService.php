<?php

namespace App\Services\Administration;

use App\Dto\Administration\UpdateRolePermissionsDTO;
use App\Enums\Administration\RoleCode;
use App\Events\Administration\RolePermissionsUpdated;
use App\Models\Administration\PermissionModel;
use App\Models\Administration\RoleModel;
use App\Models\Administration\UserModel;
use App\Repositories\Interfaces\Administration\RolePermissionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class RolePermissionManagementService
{
    public function __construct(
        private readonly RolePermissionRepositoryInterface $rolePermissions,
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
            function () use ($role, $data): array {
                $lockedRole = $this->rolePermissions
                    ->repo_findRoleForUpdateByUuid($role->uuid);

                if ($lockedRole->role_code === RoleCode::SystemAdmin->value) {
                    throw ValidationException::withMessages([
                        'permissions' => 'Les permissions de l’administrateur système sont protégées.',
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
