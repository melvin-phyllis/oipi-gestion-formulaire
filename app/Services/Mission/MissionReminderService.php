<?php

namespace App\Services\Mission;

use App\Dto\Mission\MissionReminderCandidateDTO;
use App\Enums\Mission\MissionReminderType;
use App\Events\Mission\MissionReminderSent;
use App\Jobs\Mission\SendMissionReminder;
use App\Models\Mission\MissionReminderModel;
use App\Notifications\Mission\MissionReminderNotification;
use App\Repositories\Interfaces\Mission\MissionReminderRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Throwable;

final class MissionReminderService
{
    public function __construct(private readonly MissionReminderRepositoryInterface $reminders) {}

    public function srv_dispatchScheduled(?CarbonImmutable $date = null): int
    {
        $today = ($date ?? CarbonImmutable::today())->startOfDay();
        $beforeDays = max(1, (int) config('risfm.mission_reminders.before_due_days', 2));
        $escalationDays = max(1, (int) config('risfm.mission_reminders.escalation_after_days', 3));
        $missions = $this->reminders->repo_dueMissions($today->addDays($beforeDays));
        $escalationRecipients = null;
        $dispatched = 0;

        foreach ($missions as $mission) {
            $daysDelta = (int) $today->diffInDays($mission->due_date, false);
            $responsibleCode = $mission->responsible_user_code_unique;

            if ($daysDelta > 0 && $daysDelta <= $beforeDays) {
                if (! $this->reminders->repo_hasReminderType(
                    $mission->search_mission_code_unique,
                    $responsibleCode,
                    MissionReminderType::BeforeDue,
                )) {
                    $this->dispatch(
                        $mission->search_mission_code_unique,
                        $responsibleCode,
                        MissionReminderType::BeforeDue,
                        $today,
                        $daysDelta,
                    );
                    $dispatched++;
                }

                continue;
            }

            if ($daysDelta === 0) {
                $this->dispatch(
                    $mission->search_mission_code_unique,
                    $responsibleCode,
                    MissionReminderType::DueToday,
                    $today,
                    0,
                );
                $dispatched++;

                continue;
            }

            if ($daysDelta < 0) {
                $this->dispatch(
                    $mission->search_mission_code_unique,
                    $responsibleCode,
                    MissionReminderType::Overdue,
                    $today,
                    $daysDelta,
                );
                $dispatched++;

                if ($daysDelta <= -$escalationDays) {
                    $escalationRecipients ??= $this->reminders->repo_escalationRecipients();
                    foreach ($escalationRecipients as $recipient) {
                        if ($this->reminders->repo_hasReminderType(
                            $mission->search_mission_code_unique,
                            $recipient->user_code_unique,
                            MissionReminderType::Escalation,
                        )) {
                            continue;
                        }

                        $this->dispatch(
                            $mission->search_mission_code_unique,
                            $recipient->user_code_unique,
                            MissionReminderType::Escalation,
                            $today,
                            $daysDelta,
                        );
                        $dispatched++;
                    }
                }
            }
        }

        return $dispatched;
    }

    public function srv_send(MissionReminderCandidateDTO $candidate): ?MissionReminderModel
    {
        [$reminder, $mission, $recipient] = DB::transaction(function () use ($candidate): array {
            $mission = $this->reminders->repo_lockActiveMission($candidate->missionCodeUnique);
            $recipient = $this->reminders->repo_findActiveRecipient($candidate->recipientUserCodeUnique);
            if (! $mission || ! $recipient || ! $mission->due_date) {
                return [null, null, null];
            }
            if (! $this->recipientIsStillEligible($candidate, $mission->responsible_user_code_unique)) {
                return [null, null, null];
            }
            if (! $this->isStillApplicable($candidate, $mission->due_date)) {
                return [null, null, null];
            }

            return [
                $this->reminders->repo_findOrCreate($candidate),
                $mission,
                $recipient,
            ];
        });

        if (! $reminder || ! $mission || ! $recipient) {
            return null;
        }

        $notification = new MissionReminderNotification(
            reminderType: $candidate->type,
            missionUuid: $mission->uuid,
            missingFormUuid: $mission->missingForm->uuid,
            registryNumber: $mission->missingForm->registry_number,
            dueDate: $mission->due_date,
            daysDelta: $candidate->daysDelta,
        );
        $notification->id = $reminder->uuid;
        $sent = false;

        if (! $this->reminders->repo_notificationExists($reminder->uuid)) {
            Notification::sendNow($recipient, $notification, ['database']);
            $sent = true;
        }
        if (! $reminder->database_sent_at || $reminder->notification_id !== $reminder->uuid) {
            $reminder = $this->reminders->repo_markDatabaseSent($reminder, $reminder->uuid);
        }

        if ((bool) config('risfm.mission_reminders.mail_enabled', false) && ! $reminder->email_sent_at) {
            $reminder = $this->reminders->repo_markEmailAttempt($reminder);
            try {
                Notification::sendNow($recipient, $notification, ['mail']);
                $reminder = $this->reminders->repo_markEmailSent($reminder);
                $sent = true;
            } catch (Throwable $exception) {
                $this->reminders->repo_markEmailFailed($reminder, $exception->getMessage());

                throw $exception;
            }
        }

        if ($sent) {
            MissionReminderSent::dispatch(
                $reminder->uuid,
                $mission->uuid,
                $recipient->uuid,
                $candidate->type,
            );
        }

        return $reminder->refresh();
    }

    private function dispatch(
        string $missionCodeUnique,
        string $recipientCodeUnique,
        MissionReminderType $type,
        CarbonImmutable $date,
        int $daysDelta,
    ): void {
        SendMissionReminder::dispatch(
            missionCodeUnique: $missionCodeUnique,
            recipientUserCodeUnique: $recipientCodeUnique,
            type: $type,
            reminderDate: $date->toDateString(),
            daysDelta: $daysDelta,
        );
    }

    private function isStillApplicable(
        MissionReminderCandidateDTO $candidate,
        CarbonImmutable $dueDate,
    ): bool {
        $currentDelta = (int) $candidate->reminderDate->diffInDays($dueDate, false);
        $beforeDays = max(1, (int) config('risfm.mission_reminders.before_due_days', 2));
        $escalationDays = max(1, (int) config('risfm.mission_reminders.escalation_after_days', 3));

        return match ($candidate->type) {
            MissionReminderType::BeforeDue => $currentDelta > 0 && $currentDelta <= $beforeDays,
            MissionReminderType::DueToday => $currentDelta === 0,
            MissionReminderType::Overdue => $currentDelta < 0,
            MissionReminderType::Escalation => $currentDelta <= -$escalationDays,
        };
    }

    private function recipientIsStillEligible(
        MissionReminderCandidateDTO $candidate,
        string $responsibleUserCodeUnique,
    ): bool {
        if ($candidate->type === MissionReminderType::Escalation) {
            return $this->reminders->repo_recipientCanReceiveEscalations($candidate->recipientUserCodeUnique);
        }

        return $candidate->recipientUserCodeUnique === $responsibleUserCodeUnique;
    }
}
