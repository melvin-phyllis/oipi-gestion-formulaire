<?php

namespace App\Repositories\Interfaces\Administration;

use App\Models\Administration\PermissionModel;
use App\Models\Administration\RoleModel;
use Illuminate\Database\Eloquent\Collection;

interface RolePermissionRepositoryInterface
{
    /** @return Collection<int, RoleModel> */
    public function repo_activeRolesWithPermissions(): Collection;

    /** @return Collection<int, PermissionModel> */
    public function repo_activePermissions(): Collection;

    public function repo_findRoleForUpdateByUuid(string $roleUuid): RoleModel;

    /** @return list<string> */
    public function repo_activePermissionCodes(RoleModel $role): array;

    /** @param list<string> $permissionCodes */
    public function repo_syncActivePermissions(RoleModel $role, array $permissionCodes): RoleModel;
}
