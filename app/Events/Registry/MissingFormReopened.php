<?php

namespace App\Events\Registry;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class MissingFormReopened
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $missingFormUuid,
        public readonly string $reopeningUuid,
        public readonly int $trackingCycle,
        public readonly string $actorUuid,
    ) {}
}
