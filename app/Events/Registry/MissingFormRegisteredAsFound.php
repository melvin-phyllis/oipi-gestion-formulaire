<?php

namespace App\Events\Registry;

use App\Enums\Registry\FinderType;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class MissingFormRegisteredAsFound
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $missingFormUuid,
        public readonly string $reportUuid,
        public readonly string $actorUuid,
        public readonly FinderType $finderType,
    ) {}
}
