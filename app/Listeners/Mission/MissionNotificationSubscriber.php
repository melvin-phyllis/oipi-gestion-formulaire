<?php

namespace App\Listeners\Mission;

use App\Enums\Mission\MissionAssignmentNotificationType;
use App\Events\Mission\SearchMissionAssigned;
use App\Events\Mission\SearchMissionCancelled;
use App\Events\Mission\SearchMissionReassigned;
use App\Models\Administration\UserModel;
use App\Models\Mission\SearchMissionModel;
use App\Notifications\Mission\MissionAssignmentNotification;
use App\Repositories\Interfaces\Mission\SearchMissionRepositoryInterface;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Log;
use Throwable;

final class MissionNotificationSubscriber
{
    public function __construct(
        private readonly SearchMissionRepositoryInterface $missions,
    ) {}

    public function onAssigned(SearchMissionAssigned $event): void
    {
        $mission = $this->missions->repo_findForNotification($event->missionUuid);
        if (! $mission || ! $mission->responsible) {
            return;
        }

        $this->notify($mission->responsible, $mission, MissionAssignmentNotificationType::Assigned);
    }

    public function onCancelled(SearchMissionCancelled $event): void
    {
        $mission = $this->missions->repo_findForNotification($event->missionUuid);
        if (! $mission || ! $mission->responsible) {
            return;
        }

        $this->notify(
            $mission->responsible,
            $mission,
            MissionAssignmentNotificationType::Cancelled,
            $event->reason,
        );
    }

    public function onReassigned(SearchMissionReassigned $event): void
    {
        $previousMission = $this->missions->repo_findForNotification($event->previousMissionUuid);
        $newMission = $this->missions->repo_findForNotification($event->newMissionUuid);
        if (! $newMission || ! $newMission->responsible) {
            return;
        }

        $previousResponsible = $previousMission?->responsible;
        $sameResponsible = $previousResponsible
            && $previousResponsible->is($newMission->responsible);
        $reason = $this->reassignmentReason($previousMission?->cancellation_reason);

        if ($previousMission && $previousResponsible && ! $sameResponsible) {
            $this->notify(
                $previousResponsible,
                $previousMission,
                MissionAssignmentNotificationType::Withdrawn,
                $reason,
            );
        }

        $this->notify(
            $newMission->responsible,
            $newMission,
            MissionAssignmentNotificationType::Reassigned,
            $reason,
        );
    }

    public function subscribe(Dispatcher $events): array
    {
        return [
            SearchMissionAssigned::class => 'onAssigned',
            SearchMissionCancelled::class => 'onCancelled',
            SearchMissionReassigned::class => 'onReassigned',
        ];
    }

    private function notify(
        UserModel $recipient,
        SearchMissionModel $mission,
        MissionAssignmentNotificationType $type,
        ?string $reason = null,
    ): void {
        if (! $mission->missingForm || ! $mission->searchLocation) {
            return;
        }

        try {
            $recipient->notify(new MissionAssignmentNotification(
                notificationType: $type,
                missionUuid: $mission->uuid,
                missingFormUuid: $mission->missingForm->uuid,
                registryNumber: $mission->missingForm->registry_number,
                formNumber: $mission->missingForm->form_number,
                searchLocation: $mission->searchLocation->location_name,
                priority: $mission->priority->label(),
                dueDate: $mission->due_date,
                assignedBy: $this->actorName($mission, $type),
                reason: $reason,
            ));
        } catch (Throwable $exception) {
            Log::error('La notification de mission n’a pas pu être mise en file.', [
                'mission_uuid' => $mission->uuid,
                'recipient_uuid' => $recipient->uuid,
                'notification_type' => $type->value,
                'exception' => $exception,
            ]);
        }
    }

    private function actorName(
        SearchMissionModel $mission,
        MissionAssignmentNotificationType $type,
    ): string {
        $actor = match ($type) {
            MissionAssignmentNotificationType::Cancelled,
            MissionAssignmentNotificationType::Withdrawn => $mission->closedBy ?? $mission->assignedBy,
            default => $mission->assignedBy ?? $mission->closedBy,
        };

        return $actor
            ? trim($actor->last_name.' '.$actor->first_names)
            : 'Système RISFM';
    }

    private function reassignmentReason(?string $reason): ?string
    {
        if (! $reason) {
            return null;
        }

        return preg_replace('/^Réaffectation\s*:\s*/u', '', $reason) ?: $reason;
    }
}
