<?php

namespace App\Dto\Mission;

use App\Enums\Mission\MissionReminderType;
use Carbon\CarbonImmutable;

final readonly class MissionReminderCandidateDTO
{
    public function __construct(
        public string $missionCodeUnique,
        public string $recipientUserCodeUnique,
        public MissionReminderType $type,
        public CarbonImmutable $reminderDate,
        public int $daysDelta,
    ) {}
}
