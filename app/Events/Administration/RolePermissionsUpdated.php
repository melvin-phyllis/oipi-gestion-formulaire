<?php

namespace App\Events\Administration;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class RolePermissionsUpdated
{
    use Dispatchable, SerializesModels;

    /**
     * @param  list<string>  $addedPermissions
     * @param  list<string>  $removedPermissions
     */
    public function __construct(
        public readonly string $roleUuid,
        public readonly string $actorUuid,
        public readonly array $addedPermissions,
        public readonly array $removedPermissions,
    ) {}
}
