<?php

namespace App\Repositories\Interfaces\Administration;

interface AuthorizationRepositoryInterface
{
    public function repo_roleHasPermission(
        string $roleCodeUnique,
        string $permissionCode,
    ): bool;
}
