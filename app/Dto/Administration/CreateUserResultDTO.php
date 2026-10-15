<?php

namespace App\Dto\Administration;

final readonly class CreateUserResultDTO
{
    public function __construct(
        public string $userUuid,
        public string $temporaryPassword,
    ) {}
}
