<?php

namespace App\Services\Registry;

use App\Dto\Registry\RegisterFoundMissingFormDTO;
use App\Enums\Registry\FinderType;
use App\Events\Registry\MissingFormRegisteredAsFound;
use App\Models\Administration\UserModel;
use App\Models\Registry\MissingFormModel;
use App\Repositories\Interfaces\Registry\DirectFoundReportRepositoryInterface;
use App\Repositories\Interfaces\Registry\MissingFormRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class DirectFoundRegistrationService
{
    public function __construct(
        private readonly MissingFormRepositoryInterface $forms,
        private readonly DirectFoundReportRepositoryInterface $reports,
    ) {}

    /** @throws ValidationException */
    public function srv_register(
        RegisterFoundMissingFormDTO $data,
        UserModel $actor,
    ): MissingFormModel {
        $this->assertReferencesAndBusinessRules($data);
        $finder = $data->finderType === FinderType::Internal
            ? $this->reports->repo_findActiveFinder((string) $data->finderUserCodeUnique)
            : null;

        if ($data->finderType === FinderType::Internal && ! $finder instanceof UserModel) {
            throw ValidationException::withMessages([
                'finder_user_code_unique' => 'Le compte sélectionné est introuvable ou inactif.',
            ]);
        }

        [$form, $report] = DB::transaction(function () use ($data, $actor, $finder): array {
            $form = $this->forms->repo_create($data->missingForm(), $actor->user_code_unique);
            $report = $this->reports->repo_createReport($form, $data, $finder, $actor);
            $form = $this->reports->repo_markFormFound($form, $data, $actor);

            return [$form, $report];
        });

        MissingFormRegisteredAsFound::dispatch(
            $form->uuid,
            $report->uuid,
            $actor->uuid,
            $data->finderType,
        );

        return $form;
    }

    /** @throws ValidationException */
    private function assertReferencesAndBusinessRules(RegisterFoundMissingFormDTO $data): void
    {
        if (! $this->forms->repo_findActiveTitleType($data->titleTypeCodeUnique)) {
            throw ValidationException::withMessages([
                'title_type_code_unique' => 'Le type de titre sélectionné est introuvable ou inactif.',
            ]);
        }
        if ($this->forms->repo_businessIdentityExists(
            $data->formYear,
            $data->titleTypeCodeUnique,
            $data->formNumber,
        )) {
            throw ValidationException::withMessages([
                'form_number' => 'Ce formulaire existe déjà pour ce type de titre et cette année.',
            ]);
        }
        if (! $this->reports->repo_findActiveLocation($data->foundLocationCodeUnique)) {
            throw ValidationException::withMessages([
                'found_location_code_unique' => 'La localisation sélectionnée est introuvable ou inactive.',
            ]);
        }
        if ($data->foundOn->isFuture()) {
            throw ValidationException::withMessages([
                'found_on' => 'La date de découverte ne peut pas être située dans le futur.',
            ]);
        }
        if ($data->finderType === FinderType::Internal && blank($data->finderUserCodeUnique)) {
            throw ValidationException::withMessages([
                'finder_user_code_unique' => 'Sélectionnez la personne ayant retrouvé le formulaire.',
            ]);
        }
        if ($data->finderType === FinderType::External && blank($data->finderName)) {
            throw ValidationException::withMessages([
                'finder_name' => 'Saisissez le nom de la personne externe.',
            ]);
        }
    }
}
