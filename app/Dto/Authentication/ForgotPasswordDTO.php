<?php

namespace App\Dto\Authentication;

final readonly class ForgotPasswordDTO
{
    public function __construct(
        public string $email,
    ) {}

    /** @param array{email: string} $validated */
    public static function fromValidated(array $validated): self
    {
        return new self(email: $validated['email']);
    }
}
