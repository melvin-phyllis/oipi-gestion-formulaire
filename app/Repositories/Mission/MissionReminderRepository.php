<?php

namespace App\Repositories\Mission;

use App\Dto\Mission\MissionReminderCandidateDTO;
use App\Enums\Administration\PermissionCode;
use App\Enums\Mission\MissionReminderType;
use App\Enums\Mission\MissionStatus;
use App\Models\Administration\UserModel;
use App\Models\Mission\MissionReminderModel;
use App\Models\Mission\SearchMissionModel;
use App\Repositories\Interfaces\Mission\MissionReminderRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final class MissionReminderRepository implements MissionReminderRepositoryInterface
{
    public function repo_dueMissions(CarbonImmutable $throughDate): Collection
    {
        return SearchMissionModel::query()
            ->with(['missingForm', 'searchLocation', 'responsible'])
            ->whereIn('status', [MissionStatus::Assigned->value, MissionStatus::InProgress->value])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<=', $throughDate)
            ->orderBy('due_date')
            ->get();
    }

    public function repo_escalationRecipients(): Collection
    {
        return UserModel::query()
            ->join('adm_role_models', 'adm_role_models.role_code_unique', '=', 'adm_user_models.role_code_unique')
            ->join('adm_role_permission_models', 'adm_role_permission_models.role_code_unique', '=', 'adm_role_models.role_code_unique')
            ->join('adm_permission_models', 'adm_permission_models.permission_code_unique', '=', 'adm_role_permission_models.permission_code_unique')
            ->where('adm_user_models.is_active', true)
            ->where('adm_role_models.is_active', true)
            ->where('adm_role_permission_models.is_active', true)
            ->where('adm_permission_models.is_active', true)
            ->where('adm_permission_models.permission_code', PermissionCode::MissionsAssign->value)
            ->whereNull('adm_role_models.deleted_at')
            ->whereNull('adm_role_permission_models.deleted_at')
            ->whereNull('adm_permission_models.deleted_at')
            ->select('adm_user_models.*')
            ->distinct()
            ->get();
    }

    public function repo_hasReminderType(
        string $missionCodeUnique,
        string $recipientCodeUnique,
        MissionReminderType $type,
    ): bool {
        return MissionReminderModel::withTrashed()
            ->where('search_mission_code_unique', $missionCodeUnique)
            ->where('recipient_user_code_unique', $recipientCodeUnique)
            ->where('type', $type->value)
            ->exists();
    }

    public function repo_lockActiveMission(string $missionCodeUnique): ?SearchMissionModel
    {
        return SearchMissionModel::query()
            ->with(['missingForm', 'searchLocation'])
            ->where('search_mission_code_unique', $missionCodeUnique)
            ->whereIn('status', [MissionStatus::Assigned->value, MissionStatus::InProgress->value])
            ->lockForUpdate()
            ->first();
    }

    public function repo_findActiveRecipient(string $userCodeUnique): ?UserModel
    {
        return UserModel::query()
            ->where('user_code_unique', $userCodeUnique)
            ->where('is_active', true)
            ->first();
    }

    public function repo_recipientCanReceiveEscalations(string $userCodeUnique): bool
    {
        return UserModel::query()
            ->join('adm_role_models', 'adm_role_models.role_code_unique', '=', 'adm_user_models.role_code_unique')
            ->join('adm_role_permission_models', 'adm_role_permission_models.role_code_unique', '=', 'adm_role_models.role_code_unique')
            ->join('adm_permission_models', 'adm_permission_models.permission_code_unique', '=', 'adm_role_permission_models.permission_code_unique')
            ->where('adm_user_models.user_code_unique', $userCodeUnique)
            ->where('adm_user_models.is_active', true)
            ->where('adm_role_models.is_active', true)
            ->where('adm_role_permission_models.is_active', true)
            ->where('adm_permission_models.is_active', true)
            ->where('adm_permission_models.permission_code', PermissionCode::MissionsAssign->value)
            ->whereNull('adm_role_models.deleted_at')
            ->whereNull('adm_role_permission_models.deleted_at')
            ->whereNull('adm_permission_models.deleted_at')
            ->exists();
    }

    public function repo_findOrCreate(MissionReminderCandidateDTO $candidate): MissionReminderModel
    {
        $reminder = MissionReminderModel::withTrashed()
            ->where('search_mission_code_unique', $candidate->missionCodeUnique)
            ->where('recipient_user_code_unique', $candidate->recipientUserCodeUnique)
            ->where('type', $candidate->type->value)
            ->whereDate('reminder_date', $candidate->reminderDate->toDateString())
            ->first();

        if ($reminder) {
            return $reminder;
        }

        return MissionReminderModel::query()->create([
            'search_mission_code_unique' => $candidate->missionCodeUnique,
            'recipient_user_code_unique' => $candidate->recipientUserCodeUnique,
            'type' => $candidate->type,
            'reminder_date' => $candidate->reminderDate->toDateString(),
            'days_delta' => $candidate->daysDelta,
        ]);
    }

    public function repo_notificationExists(string $notificationId): bool
    {
        return DB::table('notifications')->where('id', $notificationId)->exists();
    }

    public function repo_markDatabaseSent(MissionReminderModel $reminder, string $notificationId): MissionReminderModel
    {
        $reminder->update([
            'notification_id' => $notificationId,
            'database_sent_at' => now(),
        ]);

        return $reminder->refresh();
    }

    public function repo_markEmailAttempt(MissionReminderModel $reminder): MissionReminderModel
    {
        $reminder->update([
            'email_attempted_at' => now(),
            'email_error' => null,
        ]);

        return $reminder->refresh();
    }

    public function repo_markEmailSent(MissionReminderModel $reminder): MissionReminderModel
    {
        $reminder->update([
            'email_sent_at' => now(),
            'email_error' => null,
        ]);

        return $reminder->refresh();
    }

    public function repo_markEmailFailed(MissionReminderModel $reminder, string $error): MissionReminderModel
    {
        $reminder->update(['email_error' => mb_substr($error, 0, 500)]);

        return $reminder->refresh();
    }
}
