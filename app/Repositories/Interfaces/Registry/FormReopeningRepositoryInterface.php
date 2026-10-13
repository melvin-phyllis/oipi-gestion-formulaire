<?php

namespace App\Repositories\Interfaces\Registry;

use App\Dto\Registry\ReopenMissingFormDTO;
use App\Models\Administration\UserModel;
use App\Models\Registry\FormReopeningModel;
use App\Models\Registry\MissingFormModel;

interface FormReopeningRepositoryInterface
{
    public function repo_lockForm(string $formCodeUnique): MissingFormModel;

    public function repo_cycleAlreadyReopened(string $formCodeUnique, int $trackingCycle): bool;

    public function repo_createRecord(
        MissingFormModel $form,
        ReopenMissingFormDTO $data,
        UserModel $actor,
    ): FormReopeningModel;

    public function repo_reopen(MissingFormModel $form, string $actorCodeUnique): MissingFormModel;
}
