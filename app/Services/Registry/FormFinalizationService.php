<?php

namespace App\Services\Registry;

use App\Dto\Registry\ConfirmFinalizationDTO;
use App\Enums\Registry\FinalizationStep;
use App\Events\Registry\FormFinalizationStepConfirmed;
use App\Models\Administration\UserModel;
use App\Models\Registry\FormFinalizationModel;
use App\Models\Registry\MissingFormModel;
use App\Repositories\Interfaces\Registry\FormFinalizationRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;

final class FormFinalizationService
{
    public function __construct(private readonly FormFinalizationRepositoryInterface $finalizations) {}

    public function srv_confirmDigitized(
        MissingFormModel $form,
        ConfirmFinalizationDTO $data,
        UserModel $actor,
    ): FormFinalizationModel {
        return $this->confirm($form, FinalizationStep::Digitized, $data, $actor);
    }

    public function srv_confirmEntered(
        MissingFormModel $form,
        ConfirmFinalizationDTO $data,
        UserModel $actor,
    ): FormFinalizationModel {
        return $this->confirm($form, FinalizationStep::Entered, $data, $actor);
    }

    private function confirm(
        MissingFormModel $form,
        FinalizationStep $step,
        ConfirmFinalizationDTO $data,
        UserModel $actor,
    ): FormFinalizationModel {
        if ($data->performedAt->isFuture()) {
            throw ValidationException::withMessages([
                'performed_on' => 'La date de réalisation ne peut pas être située dans le futur.',
            ]);
        }

        $finalization = DB::transaction(function () use ($form, $step, $data, $actor): FormFinalizationModel {
            $lockedForm = $this->finalizations->repo_lockForm($form->missing_form_code_unique);
            if ($lockedForm->status !== $step->requiredStatus()) {
                throw new LogicException(sprintf(
                    'L’étape « %s » exige que le formulaire soit au statut « %s ».',
                    $step->label(),
                    $step->requiredStatus()->label(),
                ));
            }
            if ($lockedForm->resolved_at && $data->performedAt->isBefore($lockedForm->resolved_at->startOfDay())) {
                throw ValidationException::withMessages([
                    'performed_on' => 'La date de réalisation ne peut pas précéder la découverte du formulaire.',
                ]);
            }
            if ($this->finalizations->repo_stepExists(
                $lockedForm->missing_form_code_unique,
                $lockedForm->tracking_cycle,
                $step,
            )) {
                throw new LogicException('Cette étape a déjà été enregistrée pour le cycle actuel.');
            }

            $record = $this->finalizations->repo_recordStep($lockedForm, $step, $data, $actor);
            $this->finalizations->repo_updateFormStatus($lockedForm, $step, $actor->user_code_unique);

            return $record;
        });

        FormFinalizationStepConfirmed::dispatch(
            $form->uuid,
            $finalization->uuid,
            $step,
            $actor->uuid,
        );

        return $finalization;
    }
}
