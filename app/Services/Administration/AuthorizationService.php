<?php

namespace App\Services\Administration;

use App\Enums\Administration\PermissionCode;
use App\Models\Administration\UserModel;
use App\Repositories\Interfaces\Administration\AuthorizationRepositoryInterface;
use App\Repositories\Interfaces\Administration\UserRepositoryInterface;

final class AuthorizationService
{
    public function __construct(
        private readonly AuthorizationRepositoryInterface $authorization,
        private readonly UserRepositoryInterface $users,
    ) {}

    public function srv_userHasPermission(
        UserModel $user,
        PermissionCode $permission,
    ): bool {
        if (! $user->is_active) {
            return false;
        }

        if (in_array($permission, [PermissionCode::RolesManage, PermissionCode::AuditView], true)
            && ! $this->users->repo_isActiveSystemAdministrator($user)) {
            return false;
        }

        return $this->authorization->repo_roleHasPermission(
            $user->role_code_unique,
            $permission->value,
        );
    }
}
