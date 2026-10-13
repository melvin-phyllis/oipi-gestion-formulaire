<?php

namespace App\Events\Registry;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class MissingFormCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $missingFormUuid,
        public readonly string $actorUuid,
    ) {}
}
