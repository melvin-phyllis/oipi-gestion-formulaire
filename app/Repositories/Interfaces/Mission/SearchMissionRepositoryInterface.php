<?php

namespace App\Repositories\Interfaces\Mission;

use App\Dto\Mission\AssignSearchMissionDTO;
use App\Dto\Mission\MissionFiltersDTO;
use App\Dto\Mission\RecordMissionResultDTO;
use App\Enums\Registry\FormStatus;
use App\Models\Administration\UserModel;
use App\Models\Mission\SearchHistoryModel;
use App\Models\Mission\SearchMissionModel;
use App\Models\Registry\MissingFormModel;
use App\Models\Registry\SearchLocationModel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface SearchMissionRepositoryInterface
{
    /** @return LengthAwarePaginator<int, SearchMissionModel> */
    public function repo_paginate(
        MissionFiltersDTO $filters,
        UserModel $viewer,
        bool $canViewAll,
    ): LengthAwarePaginator;

    public function repo_loadForDisplay(SearchMissionModel $mission): SearchMissionModel;

    public function repo_findForNotification(string $uuid): ?SearchMissionModel;

    public function repo_lockForm(string $formCodeUnique): MissingFormModel;

    public function repo_lockMission(string $missionCodeUnique): SearchMissionModel;

    public function repo_findActiveLocation(string $codeUnique): ?SearchLocationModel;

    public function repo_findActiveResponsible(string $codeUnique): ?UserModel;

    public function repo_activeAssignmentExists(string $assignmentKey): bool;

    public function repo_create(
        MissingFormModel $form,
        AssignSearchMissionDTO $data,
        UserModel $actor,
        string $assignmentKey,
        ?string $parentMissionCodeUnique = null,
    ): SearchMissionModel;

    public function repo_start(SearchMissionModel $mission): SearchMissionModel;

    public function repo_cancel(
        SearchMissionModel $mission,
        string $reason,
        UserModel $actor,
    ): SearchMissionModel;

    public function repo_refreshFormAfterCancellation(
        MissingFormModel $form,
        string $actorCodeUnique,
    ): MissingFormModel;

    public function repo_markFormAssigned(
        MissingFormModel $form,
        SearchMissionModel $mission,
        string $actorCodeUnique,
    ): MissingFormModel;

    public function repo_complete(
        SearchMissionModel $mission,
        RecordMissionResultDTO $data,
        UserModel $actor,
    ): SearchMissionModel;

    public function repo_countActiveForForm(string $formCodeUnique): int;

    public function repo_pendingFoundReportExistsForForm(
        string $formCodeUnique,
        int $trackingCycle,
    ): bool;

    public function repo_cancelOtherActiveForForm(
        string $formCodeUnique,
        string $exceptMissionCodeUnique,
        UserModel $actor,
    ): int;

    public function repo_updateFormAfterResult(
        MissingFormModel $form,
        SearchMissionModel $mission,
        FormStatus $status,
        bool $hasActiveMission,
        string $actorCodeUnique,
    ): MissingFormModel;

    public function repo_createHistory(
        MissingFormModel $form,
        SearchMissionModel $mission,
        UserModel $actor,
    ): SearchHistoryModel;
}
