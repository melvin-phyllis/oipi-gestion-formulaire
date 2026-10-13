<?php

namespace App\Dto\Administration;

use App\Models\Administration\UserModel;

final readonly class CreateUserResultDTO
{
    public function __construct(
        public UserModel $user,
        public string $temporaryPassword,
    ) {}
}
