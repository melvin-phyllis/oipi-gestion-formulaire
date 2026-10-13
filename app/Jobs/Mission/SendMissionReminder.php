<?php

namespace App\Jobs\Mission;

use App\Dto\Mission\MissionReminderCandidateDTO;
use App\Enums\Mission\MissionReminderType;
use App\Services\Mission\MissionReminderService;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class SendMissionReminder implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $uniqueFor = 3600;

    /** @var array<int, int> */
    public array $backoff = [60, 300, 900];

    public function __construct(
        public readonly string $missionCodeUnique,
        public readonly string $recipientUserCodeUnique,
        public readonly MissionReminderType $type,
        public readonly string $reminderDate,
        public readonly int $daysDelta,
    ) {
        $this->onQueue('notifications');
    }

    public function handle(MissionReminderService $service): void
    {
        $service->srv_send(new MissionReminderCandidateDTO(
            missionCodeUnique: $this->missionCodeUnique,
            recipientUserCodeUnique: $this->recipientUserCodeUnique,
            type: $this->type,
            reminderDate: CarbonImmutable::parse($this->reminderDate),
            daysDelta: $this->daysDelta,
        ));
    }

    public function uniqueId(): string
    {
        return implode('|', [
            $this->missionCodeUnique,
            $this->recipientUserCodeUnique,
            $this->type->value,
            $this->reminderDate,
        ]);
    }
}
