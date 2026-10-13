<?php

namespace App\Repositories\Mission;

use App\Dto\Mission\AssignSearchMissionDTO;
use App\Dto\Mission\MissionFiltersDTO;
use App\Dto\Mission\RecordMissionResultDTO;
use App\Enums\Mission\MissionResult;
use App\Enums\Mission\MissionStatus;
use App\Enums\Registry\FormPriority;
use App\Enums\Registry\FormStatus;
use App\Models\Administration\UserModel;
use App\Models\Mission\SearchHistoryModel;
use App\Models\Mission\SearchMissionModel;
use App\Models\Registry\MissingFormModel;
use App\Models\Registry\SearchLocationModel;
use App\Repositories\Interfaces\Mission\SearchMissionRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class SearchMissionRepository implements SearchMissionRepositoryInterface
{
    public function repo_paginate(
        MissionFiltersDTO $filters,
        UserModel $viewer,
        bool $canViewAll,
    ): LengthAwarePaginator {
        return SearchMissionModel::query()
            ->with([
                'missingForm.titleType',
                'searchLocation',
                'responsible.role',
                'responsible.service.directorate',
                'assignedBy',
                'foundReview',
            ])
            ->when(
                ! $canViewAll,
                fn (Builder $query): Builder => $query->where(
                    'responsible_user_code_unique',
                    $viewer->user_code_unique,
                ),
            )
            ->when($filters->search, function (Builder $query, string $search): void {
                $query->whereHas('missingForm', function (Builder $formQuery) use ($search): void {
                    $formQuery->where(function (Builder $searchQuery) use ($search): void {
                        $searchQuery
                            ->where('registry_number', 'like', "%{$search}%")
                            ->orWhere('form_number', 'like', "%{$search}%");
                    });
                });
            })
            ->when(
                $filters->responsibleUserCodeUnique,
                fn (Builder $query, string $responsible): Builder => $query->where(
                    'responsible_user_code_unique',
                    $responsible,
                ),
            )
            ->when(
                $filters->searchLocationCodeUnique,
                fn (Builder $query, string $location): Builder => $query->where(
                    'search_location_code_unique',
                    $location,
                ),
            )
            ->when(
                $filters->status,
                fn (Builder $query, MissionStatus $status): Builder => $query->where('status', $status->value),
            )
            ->when(
                $filters->priority,
                fn (Builder $query, FormPriority $priority): Builder => $query->where('priority', $priority->value),
            )
            ->when(
                $filters->dueFrom,
                fn (Builder $query, $date): Builder => $query->whereDate('due_date', '>=', $date->toDateString()),
            )
            ->when(
                $filters->dueTo,
                fn (Builder $query, $date): Builder => $query->whereDate('due_date', '<=', $date->toDateString()),
            )
            ->when($filters->overdueOnly, fn (Builder $query): Builder => $query
                ->whereIn('status', [MissionStatus::Assigned->value, MissionStatus::InProgress->value])
                ->whereDate('due_date', '<', today()))
            ->orderByDesc('assigned_at')
            ->orderByDesc('id')
            ->paginate($filters->perPage)
            ->withQueryString();
    }

    public function repo_loadForDisplay(SearchMissionModel $mission): SearchMissionModel
    {
        return $mission->load([
            'parentMission.responsible',
            'missingForm.titleType',
            'missingForm.lastSearchLocation',
            'missingForm.lastResponsible',
            'searchLocation',
            'responsible.role',
            'responsible.service.directorate',
            'assignedBy',
            'closedBy',
            'history',
            'reminders.recipient',
            'foundReview.reviewedBy',
        ]);
    }

    public function repo_findForNotification(string $uuid): ?SearchMissionModel
    {
        return SearchMissionModel::query()
            ->with([
                'missingForm',
                'searchLocation',
                'responsible',
                'assignedBy',
                'closedBy',
            ])
            ->where('uuid', $uuid)
            ->first();
    }

    public function repo_lockForm(string $formCodeUnique): MissingFormModel
    {
        return MissingFormModel::query()
            ->where('missing_form_code_unique', $formCodeUnique)
            ->lockForUpdate()
            ->firstOrFail();
    }

    public function repo_lockMission(string $missionCodeUnique): SearchMissionModel
    {
        return SearchMissionModel::query()
            ->where('search_mission_code_unique', $missionCodeUnique)
            ->lockForUpdate()
            ->firstOrFail();
    }

    public function repo_findActiveLocation(string $codeUnique): ?SearchLocationModel
    {
        return SearchLocationModel::query()
            ->where('search_location_code_unique', $codeUnique)
            ->where('is_active', true)
            ->first();
    }

    public function repo_findActiveResponsible(string $codeUnique): ?UserModel
    {
        return UserModel::query()
            ->where('user_code_unique', $codeUnique)
            ->where('is_active', true)
            ->first();
    }

    public function repo_activeAssignmentExists(string $assignmentKey): bool
    {
        return SearchMissionModel::query()
            ->where('active_assignment_key', $assignmentKey)
            ->exists();
    }

    public function repo_create(
        MissingFormModel $form,
        AssignSearchMissionDTO $data,
        UserModel $actor,
        string $assignmentKey,
        ?string $parentMissionCodeUnique = null,
    ): SearchMissionModel {
        return SearchMissionModel::query()->create([
            'parent_mission_code_unique' => $parentMissionCodeUnique,
            'missing_form_code_unique' => $form->missing_form_code_unique,
            'tracking_cycle' => $form->tracking_cycle,
            'search_location_code_unique' => $data->searchLocationCodeUnique,
            'responsible_user_code_unique' => $data->responsibleUserCodeUnique,
            'assigned_by_user_code_unique' => $actor->user_code_unique,
            'status' => MissionStatus::Assigned,
            'priority' => $data->priority,
            'assigned_at' => now(),
            'due_date' => $data->dueDate,
            'active_assignment_key' => $assignmentKey,
        ]);
    }

    public function repo_start(SearchMissionModel $mission): SearchMissionModel
    {
        $mission->update(['status' => MissionStatus::InProgress]);

        return $mission->refresh();
    }

    public function repo_cancel(
        SearchMissionModel $mission,
        string $reason,
        UserModel $actor,
    ): SearchMissionModel {
        $mission->update([
            'status' => MissionStatus::Cancelled,
            'cancellation_reason' => $reason,
            'closed_by_user_code_unique' => $actor->user_code_unique,
            'closed_at' => now(),
            'active_assignment_key' => null,
        ]);

        return $mission->refresh();
    }

    public function repo_refreshFormAfterCancellation(
        MissingFormModel $form,
        string $actorCodeUnique,
    ): MissingFormModel {
        $nextMission = SearchMissionModel::query()
            ->where('missing_form_code_unique', $form->missing_form_code_unique)
            ->whereIn('status', [MissionStatus::Assigned->value, MissionStatus::InProgress->value])
            ->orderByRaw('due_date IS NULL')
            ->orderBy('due_date')
            ->orderByDesc('assigned_at')
            ->first();
        $hasPendingFoundReport = $this->repo_pendingFoundReportExistsForForm(
            $form->missing_form_code_unique,
            $form->tracking_cycle,
        );

        $form->update([
            'status' => $hasPendingFoundReport
                ? FormStatus::NeedsVerification
                : ($nextMission ? FormStatus::InSearch : FormStatus::Missing),
            'last_search_location_code_unique' => $nextMission?->search_location_code_unique,
            'last_responsible_user_code_unique' => $nextMission?->responsible_user_code_unique,
            'search_due_date' => $nextMission?->due_date,
            'updated_by_user_code_unique' => $actorCodeUnique,
        ]);

        return $form->refresh();
    }

    public function repo_markFormAssigned(
        MissingFormModel $form,
        SearchMissionModel $mission,
        string $actorCodeUnique,
    ): MissingFormModel {
        $form->update([
            'status' => FormStatus::InSearch,
            'last_search_location_code_unique' => $mission->search_location_code_unique,
            'last_responsible_user_code_unique' => $mission->responsible_user_code_unique,
            'search_due_date' => $mission->due_date,
            'last_search_date' => null,
            'result_summary' => null,
            'updated_by_user_code_unique' => $actorCodeUnique,
        ]);

        return $form->refresh();
    }

    public function repo_complete(
        SearchMissionModel $mission,
        RecordMissionResultDTO $data,
        UserModel $actor,
    ): SearchMissionModel {
        $mission->update([
            'status' => MissionStatus::Completed,
            'result' => $data->result,
            'result_summary' => $data->resultSummary,
            'searched_on' => $data->searchedOn,
            'closed_by_user_code_unique' => $actor->user_code_unique,
            'closed_at' => now(),
            'active_assignment_key' => null,
        ]);

        return $mission->refresh();
    }

    public function repo_countActiveForForm(string $formCodeUnique): int
    {
        return SearchMissionModel::query()
            ->where('missing_form_code_unique', $formCodeUnique)
            ->whereIn('status', [MissionStatus::Assigned->value, MissionStatus::InProgress->value])
            ->count();
    }

    public function repo_pendingFoundReportExistsForForm(
        string $formCodeUnique,
        int $trackingCycle,
    ): bool {
        return SearchMissionModel::query()
            ->where('missing_form_code_unique', $formCodeUnique)
            ->where('tracking_cycle', $trackingCycle)
            ->where('status', MissionStatus::Completed->value)
            ->whereIn('result', [MissionResult::Found->value, MissionResult::NeedsVerification->value])
            ->whereDoesntHave('foundReview')
            ->exists();
    }

    public function repo_cancelOtherActiveForForm(
        string $formCodeUnique,
        string $exceptMissionCodeUnique,
        UserModel $actor,
    ): int {
        return SearchMissionModel::query()
            ->where('missing_form_code_unique', $formCodeUnique)
            ->where('search_mission_code_unique', '!=', $exceptMissionCodeUnique)
            ->whereIn('status', [MissionStatus::Assigned->value, MissionStatus::InProgress->value])
            ->update([
                'status' => MissionStatus::Cancelled->value,
                'cancellation_reason' => 'Signalement « Retrouvé » confirmé par un responsable.',
                'closed_by_user_code_unique' => $actor->user_code_unique,
                'closed_at' => now(),
                'active_assignment_key' => null,
                'updated_at' => now(),
            ]);
    }

    public function repo_updateFormAfterResult(
        MissingFormModel $form,
        SearchMissionModel $mission,
        FormStatus $status,
        bool $hasActiveMission,
        string $actorCodeUnique,
    ): MissingFormModel {
        $nextDueDate = $hasActiveMission
            ? SearchMissionModel::query()
                ->where('missing_form_code_unique', $form->missing_form_code_unique)
                ->whereIn('status', [MissionStatus::Assigned->value, MissionStatus::InProgress->value])
                ->whereNotNull('due_date')
                ->orderBy('due_date')
                ->value('due_date')
            : null;

        $form->update([
            'status' => $status,
            'last_search_location_code_unique' => $mission->search_location_code_unique,
            'last_responsible_user_code_unique' => $mission->responsible_user_code_unique,
            'search_due_date' => $nextDueDate,
            'last_search_date' => $mission->searched_on,
            'resolved_at' => $status === FormStatus::Found
                ? ($form->resolved_at ?? now())
                : $form->resolved_at,
            'result_summary' => $mission->result_summary,
            'updated_by_user_code_unique' => $actorCodeUnique,
        ]);

        return $form->refresh();
    }

    public function repo_createHistory(
        MissingFormModel $form,
        SearchMissionModel $mission,
        UserModel $actor,
    ): SearchHistoryModel {
        $mission->loadMissing(['searchLocation', 'responsible']);

        return SearchHistoryModel::query()->create([
            'search_mission_code_unique' => $mission->search_mission_code_unique,
            'missing_form_code_unique' => $form->missing_form_code_unique,
            'tracking_cycle' => $form->tracking_cycle,
            'search_location_code_unique' => $mission->search_location_code_unique,
            'search_location_name' => $mission->searchLocation->location_name,
            'responsible_user_code_unique' => $mission->responsible_user_code_unique,
            'responsible_name' => $this->userName($mission->responsible),
            'form_status' => $form->status,
            'mission_result' => $mission->result,
            'searched_on' => $mission->searched_on,
            'result_summary' => $mission->result_summary,
            'recorded_by_user_code_unique' => $actor->user_code_unique,
            'recorded_by_name' => $this->userName($actor),
            'source' => 'mission_result',
        ]);
    }

    private function userName(UserModel $user): string
    {
        return trim($user->last_name.' '.$user->first_names);
    }
}
