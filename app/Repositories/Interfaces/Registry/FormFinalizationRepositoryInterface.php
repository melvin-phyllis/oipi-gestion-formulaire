<?php

namespace App\Repositories\Interfaces\Registry;

use App\Dto\Registry\ConfirmFinalizationDTO;
use App\Enums\Registry\FinalizationStep;
use App\Models\Administration\UserModel;
use App\Models\Registry\FormFinalizationModel;
use App\Models\Registry\MissingFormModel;

interface FormFinalizationRepositoryInterface
{
    public function repo_lockForm(string $formCodeUnique): MissingFormModel;

    public function repo_stepExists(
        string $formCodeUnique,
        int $trackingCycle,
        FinalizationStep $step,
    ): bool;

    public function repo_recordStep(
        MissingFormModel $form,
        FinalizationStep $step,
        ConfirmFinalizationDTO $data,
        UserModel $actor,
    ): FormFinalizationModel;

    public function repo_updateFormStatus(
        MissingFormModel $form,
        FinalizationStep $step,
        string $actorCodeUnique,
    ): MissingFormModel;
}
