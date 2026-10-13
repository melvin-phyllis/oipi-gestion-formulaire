<?php

namespace App\Dto\Authentication;

final readonly class ChangePasswordDTO
{
    public function __construct(
        public string $currentPassword,
        public string $password,
    ) {}

    /** @param array{current_password: string, password: string} $validated */
    public static function fromValidated(array $validated): self
    {
        return new self(
            currentPassword: $validated['current_password'],
            password: $validated['password'],
        );
    }
}
