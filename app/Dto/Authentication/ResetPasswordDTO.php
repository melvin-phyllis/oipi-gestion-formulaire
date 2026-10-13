<?php

namespace App\Dto\Authentication;

final readonly class ResetPasswordDTO
{
    public function __construct(
        public string $email,
        public string $token,
        public string $password,
    ) {}

    /**
     * @param array{
     *     email: string,
     *     token: string,
     *     password: string
     * } $validated
     */
    public static function fromValidated(array $validated): self
    {
        return new self(
            email: $validated['email'],
            token: $validated['token'],
            password: $validated['password'],
        );
    }
}
