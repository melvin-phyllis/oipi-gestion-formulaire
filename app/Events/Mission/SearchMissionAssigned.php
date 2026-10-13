<?php

namespace App\Events\Mission;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class SearchMissionAssigned
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $missionUuid,
        public readonly string $missingFormUuid,
        public readonly string $responsibleUserUuid,
        public readonly string $actorUuid,
    ) {}
}
