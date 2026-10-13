<?php

namespace App\Repositories\Administration;

use App\Models\Administration\RolePermissionModel;
use App\Repositories\Interfaces\Administration\AuthorizationRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

final class AuthorizationRepository implements AuthorizationRepositoryInterface
{
    public function repo_roleHasPermission(
        string $roleCodeUnique,
        string $permissionCode,
    ): bool {
        return RolePermissionModel::query()
            ->where('role_code_unique', $roleCodeUnique)
            ->where('is_active', true)
            ->whereHas('role', fn (Builder $query): Builder => $query
                ->where('is_active', true))
            ->whereHas('permission', fn (Builder $query): Builder => $query
                ->where('permission_code', $permissionCode)
                ->where('is_active', true))
            ->exists();
    }
}
