<?php

namespace App\Repositories\Mission;

use App\Dto\Mission\ReviewFoundReportDTO;
use App\Enums\Mission\MissionStatus;
use App\Enums\Registry\FormStatus;
use App\Models\Administration\UserModel;
use App\Models\Mission\FoundReportReviewModel;
use App\Models\Mission\SearchMissionModel;
use App\Models\Registry\MissingFormModel;
use App\Repositories\Interfaces\Mission\FoundReportReviewRepositoryInterface;

final class FoundReportReviewRepository implements FoundReportReviewRepositoryInterface
{
    public function repo_reviewExists(string $missionCodeUnique): bool
    {
        return FoundReportReviewModel::withTrashed()
            ->where('search_mission_code_unique', $missionCodeUnique)
            ->exists();
    }

    public function repo_createReview(
        SearchMissionModel $mission,
        ReviewFoundReportDTO $data,
        UserModel $actor,
    ): FoundReportReviewModel {
        return FoundReportReviewModel::query()->create([
            'search_mission_code_unique' => $mission->search_mission_code_unique,
            'missing_form_code_unique' => $mission->missing_form_code_unique,
            'tracking_cycle' => $mission->tracking_cycle,
            'decision' => $data->decision,
            'rejection_reason' => $data->rejectionReason,
            'reviewed_by_user_code_unique' => $actor->user_code_unique,
            'reviewed_at' => now(),
        ]);
    }

    public function repo_confirmFormFound(
        MissingFormModel $form,
        SearchMissionModel $mission,
        UserModel $actor,
    ): MissingFormModel {
        $form->update([
            'status' => FormStatus::Found,
            'last_search_location_code_unique' => $mission->search_location_code_unique,
            'last_responsible_user_code_unique' => $mission->responsible_user_code_unique,
            'search_due_date' => null,
            'last_search_date' => $mission->searched_on,
            'resolved_at' => $form->resolved_at ?? now(),
            'result_summary' => $mission->result_summary,
            'updated_by_user_code_unique' => $actor->user_code_unique,
        ]);

        return $form->refresh();
    }

    public function repo_restoreFormAfterRejection(
        MissingFormModel $form,
        UserModel $actor,
    ): MissingFormModel {
        $nextMission = SearchMissionModel::query()
            ->where('missing_form_code_unique', $form->missing_form_code_unique)
            ->whereIn('status', [MissionStatus::Assigned->value, MissionStatus::InProgress->value])
            ->orderByRaw('due_date IS NULL')
            ->orderBy('due_date')
            ->orderByDesc('assigned_at')
            ->first();

        $form->update([
            'status' => $nextMission ? FormStatus::InSearch : FormStatus::Missing,
            'search_due_date' => $nextMission?->due_date,
            'updated_by_user_code_unique' => $actor->user_code_unique,
        ]);

        return $form->refresh();
    }
}
