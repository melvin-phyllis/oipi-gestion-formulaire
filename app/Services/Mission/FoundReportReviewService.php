<?php

namespace App\Services\Mission;

use App\Dto\Mission\ReviewFoundReportDTO;
use App\Enums\Administration\PermissionCode;
use App\Enums\Mission\FoundReportDecision;
use App\Enums\Mission\MissionResult;
use App\Enums\Mission\MissionStatus;
use App\Enums\Registry\FormStatus;
use App\Events\Mission\FoundReportConfirmed;
use App\Events\Mission\FoundReportRejected;
use App\Models\Administration\UserModel;
use App\Models\Mission\FoundReportReviewModel;
use App\Models\Mission\SearchMissionModel;
use App\Models\Registry\MissingFormModel;
use App\Repositories\Interfaces\Mission\FoundReportReviewRepositoryInterface;
use App\Repositories\Interfaces\Mission\SearchMissionRepositoryInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;

final class FoundReportReviewService
{
    public function __construct(
        private readonly FoundReportReviewRepositoryInterface $reviews,
        private readonly SearchMissionRepositoryInterface $missions,
    ) {}

    public function srv_confirm(
        SearchMissionModel $mission,
        ReviewFoundReportDTO $data,
        UserModel $actor,
    ): FoundReportReviewModel {
        if ($data->decision !== FoundReportDecision::Confirmed) {
            throw new LogicException('La décision transmise ne correspond pas à une confirmation.');
        }

        $this->assertCanReview($actor);
        [$review, $lockedMission, $form] = DB::transaction(function () use ($mission, $data, $actor): array {
            [$lockedMission, $form] = $this->lockReviewableReport($mission);
            $review = $this->reviews->repo_createReview($lockedMission, $data, $actor);
            $this->missions->repo_cancelOtherActiveForForm(
                $form->missing_form_code_unique,
                $lockedMission->search_mission_code_unique,
                $actor,
            );
            $form = $this->reviews->repo_confirmFormFound($form, $lockedMission, $actor);

            return [$review, $lockedMission, $form];
        });

        FoundReportConfirmed::dispatch(
            $review->uuid,
            $lockedMission->uuid,
            $form->uuid,
            $actor->uuid,
        );

        return $review;
    }

    public function srv_reject(
        SearchMissionModel $mission,
        ReviewFoundReportDTO $data,
        UserModel $actor,
    ): FoundReportReviewModel {
        if ($data->decision !== FoundReportDecision::Rejected) {
            throw new LogicException('La décision transmise ne correspond pas à un refus.');
        }
        if (blank($data->rejectionReason)) {
            throw ValidationException::withMessages([
                'rejection_reason' => 'Le motif du refus est obligatoire.',
            ]);
        }

        $this->assertCanReview($actor);
        [$review, $lockedMission, $form] = DB::transaction(function () use ($mission, $data, $actor): array {
            [$lockedMission, $form] = $this->lockReviewableReport($mission);
            $review = $this->reviews->repo_createReview($lockedMission, $data, $actor);
            $form = $this->reviews->repo_restoreFormAfterRejection($form, $actor);

            return [$review, $lockedMission, $form];
        });

        FoundReportRejected::dispatch(
            $review->uuid,
            $lockedMission->uuid,
            $form->uuid,
            $actor->uuid,
            $data->rejectionReason,
        );

        return $review;
    }

    private function assertCanReview(UserModel $actor): void
    {
        if (! $actor->can(PermissionCode::MissionsReviewFound->value)) {
            throw new AuthorizationException('Vous ne pouvez pas valider un formulaire signalé comme retrouvé.');
        }
    }

    /** @return array{SearchMissionModel, MissingFormModel} */
    private function lockReviewableReport(SearchMissionModel $mission): array
    {
        $lockedMission = $this->missions->repo_lockMission($mission->search_mission_code_unique);
        $form = $this->missions->repo_lockForm($lockedMission->missing_form_code_unique);

        if ($lockedMission->status !== MissionStatus::Completed
            || ! in_array($lockedMission->result, [MissionResult::Found, MissionResult::NeedsVerification], true)) {
            throw new LogicException('Seul un résultat « Retrouvé » ou « À vérifier » terminé peut être validé.');
        }
        if ($lockedMission->tracking_cycle !== $form->tracking_cycle) {
            throw new LogicException('Ce signalement appartient à un ancien cycle de recherche.');
        }
        if ($form->status !== FormStatus::NeedsVerification) {
            throw new LogicException('Ce formulaire n’est plus en attente de validation.');
        }
        if ($this->reviews->repo_reviewExists($lockedMission->search_mission_code_unique)) {
            throw new LogicException('Ce signalement a déjà été validé ou refusé.');
        }

        return [$lockedMission, $form];
    }
}
