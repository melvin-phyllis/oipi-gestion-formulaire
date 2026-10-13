<?php

namespace App\Services\Administration;

use App\Enums\Administration\PermissionCode;
use App\Models\Administration\UserModel;
use App\Repositories\Interfaces\Administration\AuthorizationRepositoryInterface;

final class AuthorizationService
{
    public function __construct(
        private readonly AuthorizationRepositoryInterface $authorization,
    ) {}

    public function srv_userHasPermission(
        UserModel $user,
        PermissionCode $permission,
    ): bool {
        if (! $user->is_active) {
            return false;
        }

        return $this->authorization->repo_roleHasPermission(
            $user->role_code_unique,
            $permission->value,
        );
    }
}
