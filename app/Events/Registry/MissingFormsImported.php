<?php

namespace App\Events\Registry;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class MissingFormsImported
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $importedCount,
        public readonly string $actorUuid,
    ) {}
}
