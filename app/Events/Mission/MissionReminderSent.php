<?php

namespace App\Events\Mission;

use App\Enums\Mission\MissionReminderType;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class MissionReminderSent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $reminderUuid,
        public readonly string $missionUuid,
        public readonly string $recipientUuid,
        public readonly MissionReminderType $type,
    ) {}
}
