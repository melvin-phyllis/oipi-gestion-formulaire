<?php

namespace App\Services\Registry;

use App\Dto\Registry\ReopenMissingFormDTO;
use App\Enums\Registry\FormStatus;
use App\Events\Registry\MissingFormReopened;
use App\Models\Administration\UserModel;
use App\Models\Registry\FormReopeningModel;
use App\Models\Registry\MissingFormModel;
use App\Repositories\Interfaces\Registry\FormReopeningRepositoryInterface;
use App\Repositories\Interfaces\Registry\MissingFormRepositoryInterface;
use Illuminate\Support\Facades\DB;
use LogicException;

final class FormReopeningService
{
    public function __construct(
        private readonly FormReopeningRepositoryInterface $reopenings,
        private readonly MissingFormRepositoryInterface $forms,
    ) {}

    public function srv_reopen(
        MissingFormModel $form,
        ReopenMissingFormDTO $data,
        UserModel $actor,
    ): FormReopeningModel {
        [$record, $form] = DB::transaction(function () use ($form, $data, $actor): array {
            $lockedForm = $this->reopenings->repo_lockForm($form->missing_form_code_unique);
            if (! in_array($lockedForm->status, [
                FormStatus::Found,
                FormStatus::Digitized,
                FormStatus::Entered,
            ], true)) {
                throw new LogicException('Seul un formulaire résolu peut être rouvert.');
            }
            if ($this->forms->repo_hasActiveMissions($lockedForm->missing_form_code_unique)) {
                throw new LogicException('Un formulaire ayant une mission active ne peut pas être rouvert.');
            }
            if ($this->reopenings->repo_cycleAlreadyReopened(
                $lockedForm->missing_form_code_unique,
                $lockedForm->tracking_cycle,
            )) {
                throw new LogicException('Ce cycle a déjà été rouvert.');
            }

            $record = $this->reopenings->repo_createRecord($lockedForm, $data, $actor);
            $form = $this->reopenings->repo_reopen($lockedForm, $actor->user_code_unique);

            return [$record, $form];
        });

        MissingFormReopened::dispatch(
            $form->uuid,
            $record->uuid,
            $form->tracking_cycle,
            $actor->uuid,
        );

        return $record;
    }
}
