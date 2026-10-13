<?php

namespace App\Dto\Administration;

final readonly class UpdateRolePermissionsDTO
{
    /** @param list<string> $permissionCodes */
    public function __construct(public array $permissionCodes) {}

    /** @param array<string, mixed> $validated */
    public static function fromValidated(array $validated): self
    {
        return new self(
            permissionCodes: array_values($validated['permissions'] ?? []),
        );
    }
}
