<?php

namespace App\Events\Organization;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class DirectorateStatusChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $directorateUuid,
        public readonly string $actorUuid,
        public readonly bool $isActive,
    ) {}
}
