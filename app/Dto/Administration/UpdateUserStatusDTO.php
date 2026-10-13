<?php

namespace App\Dto\Administration;

final readonly class UpdateUserStatusDTO
{
    public function __construct(
        public bool $isActive,
    ) {}

    /** @param array{is_active: bool} $validated */
    public static function fromValidated(array $validated): self
    {
        return new self(isActive: $validated['is_active']);
    }
}
