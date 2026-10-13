<?php

namespace App\Repositories\Interfaces\Registry;

use App\Dto\Registry\RegisterFoundMissingFormDTO;
use App\Models\Administration\UserModel;
use App\Models\Registry\DirectFoundReportModel;
use App\Models\Registry\MissingFormModel;
use App\Models\Registry\SearchLocationModel;

interface DirectFoundReportRepositoryInterface
{
    public function repo_findActiveFinder(string $userCodeUnique): ?UserModel;

    public function repo_findActiveLocation(string $locationCodeUnique): ?SearchLocationModel;

    public function repo_createReport(
        MissingFormModel $form,
        RegisterFoundMissingFormDTO $data,
        ?UserModel $finder,
        UserModel $actor,
    ): DirectFoundReportModel;

    public function repo_markFormFound(
        MissingFormModel $form,
        RegisterFoundMissingFormDTO $data,
        UserModel $actor,
    ): MissingFormModel;
}
