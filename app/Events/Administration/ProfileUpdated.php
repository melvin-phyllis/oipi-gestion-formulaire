<?php

namespace App\Events\Administration;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ProfileUpdated
{
    use Dispatchable, SerializesModels;

    /** @param list<string> $changedFields */
    public function __construct(
        public readonly string $userUuid,
        public readonly string $actorUuid,
        public readonly array $changedFields,
    ) {}
}
