<?php

namespace App\Dto\Administration;

final readonly class UpdateProfileDTO
{
    public function __construct(
        public string $lastName,
        public string $firstNames,
        public string $email,
        public ?string $phone,
        public ?string $currentPassword,
    ) {}

    /** @param array<string, mixed> $validated */
    public static function fromValidated(array $validated): self
    {
        return new self(
            lastName: $validated['last_name'],
            firstNames: $validated['first_names'],
            email: $validated['email'],
            phone: $validated['phone'] ?? null,
            currentPassword: $validated['current_password'] ?? null,
        );
    }
}
