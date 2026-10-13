<?php

namespace App\Events\Organization;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class DirectorateCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $directorateUuid,
        public readonly string $actorUuid,
    ) {}
}
