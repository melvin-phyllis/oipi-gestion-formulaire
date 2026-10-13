<?php

namespace App\Repositories\Registry;

use App\Dto\Registry\ConfirmFinalizationDTO;
use App\Enums\Registry\FinalizationStep;
use App\Models\Administration\UserModel;
use App\Models\Registry\FormFinalizationModel;
use App\Models\Registry\MissingFormModel;
use App\Repositories\Interfaces\Registry\FormFinalizationRepositoryInterface;

final class FormFinalizationRepository implements FormFinalizationRepositoryInterface
{
    public function repo_lockForm(string $formCodeUnique): MissingFormModel
    {
        return MissingFormModel::query()
            ->where('missing_form_code_unique', $formCodeUnique)
            ->lockForUpdate()
            ->firstOrFail();
    }

    public function repo_stepExists(
        string $formCodeUnique,
        int $trackingCycle,
        FinalizationStep $step,
    ): bool {
        return FormFinalizationModel::withTrashed()
            ->where('missing_form_code_unique', $formCodeUnique)
            ->where('tracking_cycle', $trackingCycle)
            ->where('step', $step->value)
            ->exists();
    }

    public function repo_recordStep(
        MissingFormModel $form,
        FinalizationStep $step,
        ConfirmFinalizationDTO $data,
        UserModel $actor,
    ): FormFinalizationModel {
        return FormFinalizationModel::query()->create([
            'missing_form_code_unique' => $form->missing_form_code_unique,
            'tracking_cycle' => $form->tracking_cycle,
            'step' => $step,
            'performed_by_user_code_unique' => $actor->user_code_unique,
            'performed_by_name' => trim($actor->last_name.' '.$actor->first_names),
            'performed_at' => $data->performedAt,
        ]);
    }

    public function repo_updateFormStatus(
        MissingFormModel $form,
        FinalizationStep $step,
        string $actorCodeUnique,
    ): MissingFormModel {
        $form->update([
            'status' => $step->resultingStatus(),
            'updated_by_user_code_unique' => $actorCodeUnique,
        ]);

        return $form->refresh();
    }
}
