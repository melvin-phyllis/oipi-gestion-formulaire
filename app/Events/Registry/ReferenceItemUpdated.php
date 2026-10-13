<?php

namespace App\Events\Registry;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ReferenceItemUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $referenceType,
        public readonly string $referenceUuid,
        public readonly string $actorUuid,
    ) {}
}
