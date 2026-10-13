<?php

namespace App\Dto\Authentication;

final readonly class LoginDTO
{
    public function __construct(
        public string $login,
        public string $password,
        public bool $remember,
    ) {}

    /** @param array{login: string, password: string, remember?: bool} $validated */
    public static function fromValidated(array $validated): self
    {
        return new self(
            login: $validated['login'],
            password: $validated['password'],
            remember: (bool) ($validated['remember'] ?? false),
        );
    }
}
