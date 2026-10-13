<?php

namespace App\Repositories\Registry;

use App\Dto\Registry\ReopenMissingFormDTO;
use App\Enums\Registry\FormStatus;
use App\Models\Administration\UserModel;
use App\Models\Registry\FormReopeningModel;
use App\Models\Registry\MissingFormModel;
use App\Repositories\Interfaces\Registry\FormReopeningRepositoryInterface;

final class FormReopeningRepository implements FormReopeningRepositoryInterface
{
    public function repo_lockForm(string $formCodeUnique): MissingFormModel
    {
        return MissingFormModel::query()
            ->where('missing_form_code_unique', $formCodeUnique)
            ->lockForUpdate()
            ->firstOrFail();
    }

    public function repo_cycleAlreadyReopened(string $formCodeUnique, int $trackingCycle): bool
    {
        return FormReopeningModel::withTrashed()
            ->where('missing_form_code_unique', $formCodeUnique)
            ->where('previous_tracking_cycle', $trackingCycle)
            ->exists();
    }

    public function repo_createRecord(
        MissingFormModel $form,
        ReopenMissingFormDTO $data,
        UserModel $actor,
    ): FormReopeningModel {
        return FormReopeningModel::query()->create([
            'missing_form_code_unique' => $form->missing_form_code_unique,
            'previous_tracking_cycle' => $form->tracking_cycle,
            'new_tracking_cycle' => $form->tracking_cycle + 1,
            'previous_status' => $form->status,
            'reason' => $data->reason,
            'reopened_by_user_code_unique' => $actor->user_code_unique,
            'reopened_by_name' => trim($actor->last_name.' '.$actor->first_names),
            'reopened_at' => now(),
        ]);
    }

    public function repo_reopen(MissingFormModel $form, string $actorCodeUnique): MissingFormModel
    {
        $form->update([
            'tracking_cycle' => $form->tracking_cycle + 1,
            'status' => FormStatus::Missing,
            'status_before_archive' => null,
            'last_search_location_code_unique' => null,
            'last_responsible_user_code_unique' => null,
            'search_due_date' => null,
            'last_search_date' => null,
            'result_summary' => null,
            'updated_by_user_code_unique' => $actorCodeUnique,
        ]);

        return $form->refresh();
    }
}
