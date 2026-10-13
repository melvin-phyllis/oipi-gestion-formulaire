<?php

namespace App\Services\Mission;

use App\Dto\Mission\AssignSearchMissionDTO;
use App\Dto\Mission\CancelSearchMissionDTO;
use App\Dto\Mission\ReassignSearchMissionDTO;
use App\Dto\Mission\RecordMissionResultDTO;
use App\Dto\Mission\StartSearchMissionDTO;
use App\Enums\Administration\PermissionCode;
use App\Enums\Mission\MissionResult;
use App\Enums\Mission\MissionStatus;
use App\Enums\Registry\FormStatus;
use App\Events\Mission\SearchMissionAssigned;
use App\Events\Mission\SearchMissionCancelled;
use App\Events\Mission\SearchMissionCompleted;
use App\Events\Mission\SearchMissionReassigned;
use App\Events\Mission\SearchMissionStarted;
use App\Models\Administration\UserModel;
use App\Models\Mission\SearchMissionModel;
use App\Models\Registry\MissingFormModel;
use App\Repositories\Interfaces\Mission\SearchMissionRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;

final class SearchMissionService
{
    public function __construct(private readonly SearchMissionRepositoryInterface $missions) {}

    public function srv_assign(
        MissingFormModel $form,
        AssignSearchMissionDTO $data,
        UserModel $actor,
    ): SearchMissionModel {
        if ($data->dueDate?->isBefore(today())) {
            throw ValidationException::withMessages([
                'due_date' => 'L’échéance ne peut pas être située dans le passé.',
            ]);
        }

        $responsible = $this->missions->repo_findActiveResponsible($data->responsibleUserCodeUnique);
        if (! $responsible) {
            throw ValidationException::withMessages([
                'responsible_user_code_unique' => 'Le responsable sélectionné est introuvable ou inactif.',
            ]);
        }
        if (! $this->missions->repo_findActiveLocation($data->searchLocationCodeUnique)) {
            throw ValidationException::withMessages([
                'search_location_code_unique' => 'La localisation sélectionnée est introuvable ou inactive.',
            ]);
        }

        $mission = DB::transaction(function () use ($form, $data, $actor): SearchMissionModel {
            $lockedForm = $this->missions->repo_lockForm($form->missing_form_code_unique);
            $this->assertAssignable($lockedForm);
            $this->assertNoPendingFoundReport($lockedForm);

            $assignmentKey = hash('sha256', implode('|', [
                $lockedForm->missing_form_code_unique,
                $data->searchLocationCodeUnique,
                $data->responsibleUserCodeUnique,
            ]));
            if ($this->missions->repo_activeAssignmentExists($assignmentKey)) {
                throw ValidationException::withMessages([
                    'responsible_user_code_unique' => 'Cette mission active existe déjà pour ce responsable et cette localisation.',
                ]);
            }

            $mission = $this->missions->repo_create($lockedForm, $data, $actor, $assignmentKey);
            $this->missions->repo_markFormAssigned($lockedForm, $mission, $actor->user_code_unique);

            return $mission;
        });

        SearchMissionAssigned::dispatch(
            $mission->uuid,
            $form->uuid,
            $responsible->uuid,
            $actor->uuid,
        );

        return $mission;
    }

    public function srv_recordResult(
        SearchMissionModel $mission,
        RecordMissionResultDTO $data,
        UserModel $actor,
    ): SearchMissionModel {
        $this->assertCanRecord($mission, $actor);

        [$mission, $form] = DB::transaction(function () use ($mission, $data, $actor): array {
            $lockedMission = $this->missions->repo_lockMission($mission->search_mission_code_unique);
            if (! $lockedMission->status->isActive()) {
                throw new LogicException('Cette mission est déjà clôturée.');
            }
            if ($data->searchedOn->isFuture()) {
                throw ValidationException::withMessages([
                    'searched_on' => 'La date de recherche ne peut pas être située dans le futur.',
                ]);
            }
            if ($data->searchedOn->isBefore($lockedMission->assigned_at->startOfDay())) {
                throw ValidationException::withMessages([
                    'searched_on' => 'La date de recherche ne peut pas précéder l’affectation de la mission.',
                ]);
            }

            $form = $this->missions->repo_lockForm($lockedMission->missing_form_code_unique);
            if ($form->status === FormStatus::Archived) {
                throw new LogicException('Une mission liée à un formulaire archivé ne peut pas être clôturée.');
            }
            if (in_array($data->result, [MissionResult::Found, MissionResult::NeedsVerification], true)
                && $this->missions->repo_pendingFoundReportExistsForForm(
                    $form->missing_form_code_unique,
                    $form->tracking_cycle,
                )) {
                throw ValidationException::withMessages([
                    'result' => 'Un autre résultat attend déjà la validation d’un responsable.',
                ]);
            }

            $lockedMission = $this->missions->repo_complete($lockedMission, $data, $actor);

            $hasActiveMission = $this->missions->repo_countActiveForForm($form->missing_form_code_unique) > 0;
            $hasPendingFoundReport = $this->missions->repo_pendingFoundReportExistsForForm(
                $form->missing_form_code_unique,
                $form->tracking_cycle,
            );
            $newStatus = $this->statusAfterResult($data->result, $hasActiveMission, $hasPendingFoundReport);
            $form = $this->missions->repo_updateFormAfterResult(
                $form,
                $lockedMission,
                $newStatus,
                $hasActiveMission,
                $actor->user_code_unique,
            );
            $this->missions->repo_createHistory($form, $lockedMission, $actor);

            return [$lockedMission, $form];
        });

        SearchMissionCompleted::dispatch($mission->uuid, $form->uuid, $data->result, $actor->uuid);

        return $mission;
    }

    public function srv_start(
        SearchMissionModel $mission,
        StartSearchMissionDTO $data,
        UserModel $actor,
    ): SearchMissionModel {
        $this->assertCanRecord($mission, $actor);

        $mission = DB::transaction(function () use ($mission): SearchMissionModel {
            $lockedMission = $this->missions->repo_lockMission($mission->search_mission_code_unique);
            if ($lockedMission->status !== MissionStatus::Assigned) {
                throw new LogicException('Seule une mission affectée peut être démarrée.');
            }

            return $this->missions->repo_start($lockedMission);
        });

        SearchMissionStarted::dispatch($mission->uuid, $actor->uuid);

        return $mission;
    }

    public function srv_cancel(
        SearchMissionModel $mission,
        CancelSearchMissionDTO $data,
        UserModel $actor,
    ): SearchMissionModel {
        [$mission, $form] = DB::transaction(function () use ($mission, $data, $actor): array {
            $lockedMission = $this->missions->repo_lockMission($mission->search_mission_code_unique);
            $this->assertActiveMission($lockedMission);
            $form = $this->missions->repo_lockForm($lockedMission->missing_form_code_unique);

            $lockedMission = $this->missions->repo_cancel($lockedMission, $data->reason, $actor);
            $form = $this->missions->repo_refreshFormAfterCancellation($form, $actor->user_code_unique);

            return [$lockedMission, $form];
        });

        SearchMissionCancelled::dispatch($mission->uuid, $form->uuid, $data->reason, $actor->uuid);

        return $mission;
    }

    public function srv_reassign(
        SearchMissionModel $mission,
        ReassignSearchMissionDTO $data,
        UserModel $actor,
    ): SearchMissionModel {
        $this->assertDueDate($data->dueDate);
        $responsible = $this->assertAssignmentReferences(
            $data->responsibleUserCodeUnique,
            $data->searchLocationCodeUnique,
        );

        [$newMission, $form] = DB::transaction(function () use ($mission, $data, $actor): array {
            $previousMission = $this->missions->repo_lockMission($mission->search_mission_code_unique);
            $this->assertActiveMission($previousMission);
            $form = $this->missions->repo_lockForm($previousMission->missing_form_code_unique);
            $this->assertAssignable($form);
            $this->assertNoPendingFoundReport($form);

            $assignment = $data->assignment();
            $assignmentKey = $this->assignmentKey($form, $assignment);
            if ($assignmentKey === $previousMission->active_assignment_key) {
                throw ValidationException::withMessages([
                    'responsible_user_code_unique' => 'La nouvelle affectation doit modifier le responsable ou la localisation.',
                ]);
            }
            if ($this->missions->repo_activeAssignmentExists($assignmentKey)) {
                throw ValidationException::withMessages([
                    'responsible_user_code_unique' => 'Une mission active existe déjà avec cette affectation.',
                ]);
            }

            $this->missions->repo_cancel(
                $previousMission,
                'Réaffectation : '.$data->reason,
                $actor,
            );
            $newMission = $this->missions->repo_create(
                $form,
                $assignment,
                $actor,
                $assignmentKey,
                $previousMission->search_mission_code_unique,
            );
            $form = $this->missions->repo_markFormAssigned($form, $newMission, $actor->user_code_unique);

            return [$newMission, $form];
        });

        SearchMissionReassigned::dispatch(
            $mission->uuid,
            $newMission->uuid,
            $form->uuid,
            $responsible->uuid,
            $actor->uuid,
        );

        return $newMission;
    }

    private function assertAssignable(MissingFormModel $form): void
    {
        if (in_array($form->status, [
            FormStatus::Found,
            FormStatus::Digitized,
            FormStatus::Entered,
            FormStatus::Archived,
        ], true)) {
            throw new LogicException('Ce formulaire doit être rouvert avant de créer une nouvelle mission.');
        }
    }

    private function assertCanRecord(SearchMissionModel $mission, UserModel $actor): void
    {
        if ($mission->responsible_user_code_unique === $actor->user_code_unique) {
            return;
        }

        if (! $actor->can(PermissionCode::MissionsRecordAnyResult->value)) {
            throw new AuthorizationException('Vous ne pouvez pas saisir le résultat de cette mission.');
        }
    }

    private function assertActiveMission(SearchMissionModel $mission): void
    {
        if (! $mission->status->isActive()) {
            throw new LogicException('Cette mission est déjà clôturée.');
        }
    }

    private function assertDueDate(?CarbonImmutable $dueDate): void
    {
        if ($dueDate?->isBefore(today())) {
            throw ValidationException::withMessages([
                'due_date' => 'L’échéance ne peut pas être située dans le passé.',
            ]);
        }
    }

    private function assertAssignmentReferences(
        string $responsibleCodeUnique,
        string $locationCodeUnique,
    ): UserModel {
        $responsible = $this->missions->repo_findActiveResponsible($responsibleCodeUnique);
        if (! $responsible) {
            throw ValidationException::withMessages([
                'responsible_user_code_unique' => 'Le responsable sélectionné est introuvable ou inactif.',
            ]);
        }
        if (! $this->missions->repo_findActiveLocation($locationCodeUnique)) {
            throw ValidationException::withMessages([
                'search_location_code_unique' => 'La localisation sélectionnée est introuvable ou inactive.',
            ]);
        }

        return $responsible;
    }

    private function assignmentKey(
        MissingFormModel $form,
        AssignSearchMissionDTO $data,
    ): string {
        return hash('sha256', implode('|', [
            $form->missing_form_code_unique,
            $data->searchLocationCodeUnique,
            $data->responsibleUserCodeUnique,
        ]));
    }

    private function assertNoPendingFoundReport(MissingFormModel $form): void
    {
        if ($this->missions->repo_pendingFoundReportExistsForForm(
            $form->missing_form_code_unique,
            $form->tracking_cycle,
        )) {
            throw new LogicException('Un résultat « Retrouvé » ou « À vérifier » doit être validé ou refusé avant une nouvelle affectation.');
        }
    }

    private function statusAfterResult(
        MissionResult $result,
        bool $hasActiveMission,
        bool $hasPendingFoundReport,
    ): FormStatus {
        if ($hasPendingFoundReport) {
            return FormStatus::NeedsVerification;
        }
        if ($hasActiveMission) {
            return FormStatus::InSearch;
        }

        return $result === MissionResult::NeedsVerification
            ? FormStatus::NeedsVerification
            : FormStatus::Missing;
    }
}
