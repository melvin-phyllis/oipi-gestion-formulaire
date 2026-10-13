<?php

namespace App\Repositories\Interfaces\Mission;

use App\Dto\Mission\MissionReminderCandidateDTO;
use App\Enums\Mission\MissionReminderType;
use App\Models\Administration\UserModel;
use App\Models\Mission\MissionReminderModel;
use App\Models\Mission\SearchMissionModel;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

interface MissionReminderRepositoryInterface
{
    /** @return Collection<int, SearchMissionModel> */
    public function repo_dueMissions(CarbonImmutable $throughDate): Collection;

    /** @return Collection<int, UserModel> */
    public function repo_escalationRecipients(): Collection;

    public function repo_hasReminderType(
        string $missionCodeUnique,
        string $recipientCodeUnique,
        MissionReminderType $type,
    ): bool;

    public function repo_lockActiveMission(string $missionCodeUnique): ?SearchMissionModel;

    public function repo_findActiveRecipient(string $userCodeUnique): ?UserModel;

    public function repo_recipientCanReceiveEscalations(string $userCodeUnique): bool;

    public function repo_findOrCreate(MissionReminderCandidateDTO $candidate): MissionReminderModel;

    public function repo_notificationExists(string $notificationId): bool;

    public function repo_markDatabaseSent(MissionReminderModel $reminder, string $notificationId): MissionReminderModel;

    public function repo_markEmailAttempt(MissionReminderModel $reminder): MissionReminderModel;

    public function repo_markEmailSent(MissionReminderModel $reminder): MissionReminderModel;

    public function repo_markEmailFailed(MissionReminderModel $reminder, string $error): MissionReminderModel;
}
