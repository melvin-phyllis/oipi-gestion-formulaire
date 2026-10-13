<?php

namespace App\Dto\Administration;

final readonly class CreateUserDTO
{
    public function __construct(
        public string $roleCodeUnique,
        public string $serviceCodeUnique,
        public ?string $username,
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
            username: $validated['username'] ?? null,
            lastName: $validated['last_name'],
            firstNames: $validated['first_names'],
            email: $validated['email'],
            phone: $validated['phone'] ?? null,
        );
    }

    public function withUsername(string $username): self
    {
        return new self(
            roleCodeUnique: $this->roleCodeUnique,
            serviceCodeUnique: $this->serviceCodeUnique,
            username: $username,
            lastName: $this->lastName,
            firstNames: $this->firstNames,
            email: $this->email,
            phone: $this->phone,
        );
    }
}
