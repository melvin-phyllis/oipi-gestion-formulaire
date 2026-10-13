<?php

namespace App\Dto\Administration;

final readonly class UpdateUserDTO
{
    public function __construct(
        public string $roleCodeUnique,
        public string $serviceCodeUnique,
        public string $username,
        public string $lastName,
        public string $firstNames,
        public string $email,
        public ?string $phone,
    ) {}

    /** @param array<string, mixed> $validated */
    public static function fromValidated(array $validated): self
    {
        return new self(
            roleCodeUnique: $validated['role_code_unique'],
            serviceCodeUnique: $validated['service_code_unique'],
            username: $validated['username'],
            lastName: $validated['last_name'],
            firstNames: $validated['first_names'],
            email: $validated['email'],
            phone: $validated['phone'] ?? null,
        );
    }
}
