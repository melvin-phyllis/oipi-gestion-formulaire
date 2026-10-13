<?php

namespace App\Events\Mission;

use App\Enums\Mission\MissionResult;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class SearchMissionCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $missionUuid,
        public readonly string $missingFormUuid,
        public readonly MissionResult $result,
        public readonly string $actorUuid,
    ) {}
}
