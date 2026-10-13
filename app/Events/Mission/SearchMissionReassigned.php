<?php

namespace App\Events\Mission;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class SearchMissionReassigned
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $previousMissionUuid,
        public readonly string $newMissionUuid,
        public readonly string $missingFormUuid,
        public readonly string $responsibleUserUuid,
        public readonly string $actorUuid,
    ) {}
}
