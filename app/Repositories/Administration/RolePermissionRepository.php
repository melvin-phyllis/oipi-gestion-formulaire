<?php

namespace App\Repositories\Administration;

use App\Models\Administration\PermissionModel;
use App\Models\Administration\RoleModel;
use App\Models\Administration\RolePermissionModel;
use App\Repositories\Interfaces\Administration\RolePermissionRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

final class RolePermissionRepository implements RolePermissionRepositoryInterface
{
    public function repo_activeRolesWithPermissions(): Collection
    {
        return RoleModel::query()
            ->with(['permissions' => function (BelongsToMany $query): void {
                $query
                    ->where('adm_permission_models.is_active', true)
                    ->wherePivot('is_active', true)
                    ->orderBy('group_code')
                    ->orderBy('permission_name');
            }])
            ->where('is_active', true)
            ->orderBy('role_name')
            ->get();
    }

    public function repo_activePermissions(): Collection
    {
        return PermissionModel::query()
            ->where('is_active', true)
            ->orderBy('group_code')
            ->orderBy('permission_name')
            ->get();
    }

    public function repo_findRoleForUpdateByUuid(string $roleUuid): RoleModel
    {
        return RoleModel::query()
            ->where('uuid', $roleUuid)
            ->where('is_active', true)
            ->lockForUpdate()
            ->firstOrFail();
    }

    public function repo_activePermissionCodes(RoleModel $role): array
    {
        return RolePermissionModel::query()
            ->with('permission')
            ->where('role_code_unique', $role->role_code_unique)
            ->where('is_active', true)
            ->whereHas(
                'permission',
                fn (Builder $query): Builder => $query->where('is_active', true),
            )
            ->get()
            ->map(fn (RolePermissionModel $assignment): string => $assignment->permission->permission_code)
            ->sort()
            ->values()
            ->all();
    }

    public function repo_syncActivePermissions(RoleModel $role, array $permissionCodes): RoleModel
    {
        $permissions = PermissionModel::query()
            ->where('is_active', true)
            ->whereIn('permission_code', $permissionCodes)
            ->get();

        RolePermissionModel::query()
            ->where('role_code_unique', $role->role_code_unique)
            ->update(['is_active' => false]);

        foreach ($permissions as $permission) {
            $assignment = RolePermissionModel::withTrashed()->firstOrNew([
                'role_code_unique' => $role->role_code_unique,
                'permission_code_unique' => $permission->permission_code_unique,
            ]);
            $assignment->is_active = true;
            $assignment->deleted_at = null;
            $assignment->save();
        }

        return $role->refresh()->load(['permissions' => function (BelongsToMany $query): void {
            $query
                ->where('adm_permission_models.is_active', true)
                ->wherePivot('is_active', true)
                ->orderBy('group_code')
                ->orderBy('permission_name');
        }]);
    }
}
