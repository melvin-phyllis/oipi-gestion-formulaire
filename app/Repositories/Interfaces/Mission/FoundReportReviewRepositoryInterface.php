<?php

namespace App\Repositories\Interfaces\Mission;

use App\Dto\Mission\ReviewFoundReportDTO;
use App\Models\Administration\UserModel;
use App\Models\Mission\FoundReportReviewModel;
use App\Models\Mission\SearchMissionModel;
use App\Models\Registry\MissingFormModel;

interface FoundReportReviewRepositoryInterface
{
    public function repo_reviewExists(string $missionCodeUnique): bool;

    public function repo_createReview(
        SearchMissionModel $mission,
        ReviewFoundReportDTO $data,
        UserModel $actor,
    ): FoundReportReviewModel;

    public function repo_confirmFormFound(
        MissingFormModel $form,
        SearchMissionModel $mission,
        UserModel $actor,
    ): MissingFormModel;

    public function repo_restoreFormAfterRejection(
        MissingFormModel $form,
        UserModel $actor,
    ): MissingFormModel;
}
