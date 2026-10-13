<?php

namespace App\Events\Administration;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class UserCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $userUuid,
        public readonly string $actorUuid,
    ) {}
}
