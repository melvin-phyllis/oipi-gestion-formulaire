<?php

namespace App\Dto\Administration;

final readonly class UserFiltersDTO
{
    public function __construct(
        public ?string $search,
        public ?string $roleCodeUnique,
        public ?string $serviceCodeUnique,
        public ?bool $isActive,
    ) {}

    /** @param array<string, mixed> $validated */
    public static function fromValidated(array $validated): self
    {
        return new self(
            search: $validated['search'] ?? null,
            roleCodeUnique: $validated['role_code_unique'] ?? null,
            serviceCodeUnique: $validated['service_code_unique'] ?? null,
            isActive: isset($validated['is_active']) && $validated['is_active'] !== ''
                ? (bool) $validated['is_active']
                : null,
        );
    }
}
