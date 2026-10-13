<?php

namespace App\Dto\Administration;

final readonly class ResetUserPasswordDTO
{
    public function __construct(
        public string $currentPassword,
    ) {}

    /** @param array{current_password: string} $validated */
    public static function fromValidated(array $validated): self
    {
        return new self(currentPassword: $validated['current_password']);
    }
}
