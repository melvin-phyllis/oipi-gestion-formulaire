<?php

namespace App\Services\Registry;

use App\Contracts\Administration\UserDirectoryServiceInterface;
use App\Dto\Registry\ArchiveMissingFormDTO;
use App\Dto\Registry\CreateMissingFormDTO;
use App\Dto\Registry\MissingFormFiltersDTO;
use App\Dto\Registry\UpdateMissingFormDTO;
use App\Enums\Registry\FormPriority;
use App\Enums\Registry\FormStatus;
use App\Events\Registry\MissingFormArchived;
use App\Events\Registry\MissingFormCreated;
use App\Events\Registry\MissingFormMetadataUpdated;
use App\Events\Registry\MissingFormRestored;
use App\Models\Administration\UserModel;
use App\Models\Registry\MissingFormModel;
use App\Models\Registry\SearchLocationModel;
use App\Models\Registry\TitleTypeModel;
use App\Repositories\Interfaces\Registry\MissingFormRepositoryInterface;
use App\Repositories\Interfaces\Registry\RegistryReferenceRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;

final class MissingFormService
{
    public function __construct(
        private readonly MissingFormRepositoryInterface $forms,
        private readonly RegistryReferenceRepositoryInterface $references,
        private readonly UserDirectoryServiceInterface $users,
    ) {}

    /**
     * @return array{
     *     forms: LengthAwarePaginator<int, MissingFormModel>,
     *     titleTypes: Collection<int, TitleTypeModel>,
     *     statuses: array<int, FormStatus>,
     *     priorities: array<int, FormPriority>,
     *     searchLocations: Collection<int, SearchLocationModel>,
     *     finderUsers: Collection<int, UserModel>
     * }
     */
    public function srv_list(MissingFormFiltersDTO $filters): array
    {
        return [
            'forms' => $this->srv_paginate($filters),
            'titleTypes' => $this->references->repo_titleTypes(),
            'statuses' => FormStatus::cases(),
            'priorities' => FormPriority::cases(),
        ];
    }

    /**
     * @return array{
     *     titleTypes: Collection<int, TitleTypeModel>,
     *     priorities: array<int, FormPriority>
     * }
     */
    public function srv_createData(): array
    {
        return [
            'titleTypes' => $this->references->repo_availableTitleTypes(),
            'priorities' => FormPriority::cases(),
            'searchLocations' => $this->references->repo_activeSearchLocations(),
            'finderUsers' => $this->users->srv_activeUsers(),
        ];
    }

    /**
     * @return array{
     *     missingForm: MissingFormModel,
     *     searchLocations: Collection<int, SearchLocationModel>,
     *     responsibleUsers: Collection<int, UserModel>,
     *     priorities: array<int, FormPriority>,
     *     hasActiveMissions: bool
     * }
     */
    public function srv_showData(MissingFormModel $form): array
    {
        $form = $this->forms->repo_loadForDisplay($form);

        return [
            'missingForm' => $form,
            'searchLocations' => $this->references->repo_activeSearchLocations(),
            'responsibleUsers' => $this->users->srv_activeUsers(),
            'priorities' => FormPriority::cases(),
            'hasActiveMissions' => $form->missions->contains(
                fn ($mission): bool => $mission->status->isActive(),
            ),
        ];
    }

    /**
     * @return array{
     *     missingForm: MissingFormModel,
     *     titleTypes: Collection<int, TitleTypeModel>,
     *     priorities: array<int, FormPriority>
     * }
     */
    public function srv_editData(MissingFormModel $form): array
    {
        return [
            'missingForm' => $this->forms->repo_loadForDisplay($form),
            'titleTypes' => $this->references->repo_availableTitleTypes($form->title_type_code_unique),
            'priorities' => FormPriority::cases(),
        ];
    }

    /** @return LengthAwarePaginator<int, MissingFormModel> */
    public function srv_paginate(MissingFormFiltersDTO $filters, int $perPage = 20): LengthAwarePaginator
    {
        return $this->forms->repo_paginate($filters, $perPage);
    }

    public function srv_create(CreateMissingFormDTO $data, UserModel $actor): MissingFormModel
    {
        $this->assertActiveTitleType($data->titleTypeCodeUnique);
        $this->assertUniqueBusinessIdentity(
            $data->formYear,
            $data->titleTypeCodeUnique,
            $data->formNumber,
        );

        $form = DB::transaction(
            fn () => $this->forms->repo_create($data, $actor->user_code_unique),
        );

        MissingFormCreated::dispatch($form->uuid, $actor->uuid);

        return $form;
    }

    public function srv_updateMetadata(
        MissingFormModel $form,
        UpdateMissingFormDTO $data,
        UserModel $actor,
    ): MissingFormModel {
        $this->assertNotArchived($form);
        $this->assertActiveTitleType($data->titleTypeCodeUnique);
        $this->assertUniqueBusinessIdentity(
            $data->formYear,
            $data->titleTypeCodeUnique,
            $data->formNumber,
            $form->missing_form_code_unique,
        );

        $form = DB::transaction(
            fn () => $this->forms->repo_updateMetadata($form, $data, $actor->user_code_unique),
        );

        MissingFormMetadataUpdated::dispatch($form->uuid, $actor->uuid);

        return $form;
    }

    public function srv_archive(
        MissingFormModel $form,
        ArchiveMissingFormDTO $data,
        UserModel $actor,
    ): MissingFormModel {
        $this->assertNotArchived($form);
        if ($this->forms->repo_hasActiveMissions($form->missing_form_code_unique)) {
            throw new LogicException('Les missions actives doivent être clôturées ou annulées avant l’archivage.');
        }
        $previousStatus = $form->status;

        $form = DB::transaction(fn () => $this->forms->repo_archive(
            $form,
            $previousStatus,
            $data->reason,
            $actor->user_code_unique,
        ));

        MissingFormArchived::dispatch($form->uuid, $actor->uuid, $data->reason);

        return $form;
    }

    public function srv_restore(MissingFormModel $form, UserModel $actor): MissingFormModel
    {
        if ($form->status !== FormStatus::Archived) {
            throw ValidationException::withMessages([
                'restore' => 'Seul un formulaire archivé peut être restauré.',
            ]);
        }

        $restoredStatus = $form->status_before_archive ?? FormStatus::Missing;
        $form = DB::transaction(
            fn () => $this->forms->repo_restore($form, $restoredStatus, $actor->user_code_unique),
        );

        MissingFormRestored::dispatch($form->uuid, $actor->uuid);

        return $form;
    }

    private function assertActiveTitleType(string $codeUnique): void
    {
        if (! $this->forms->repo_findActiveTitleType($codeUnique)) {
            throw ValidationException::withMessages([
                'title_type_code_unique' => 'Le type de titre sélectionné est introuvable ou inactif.',
            ]);
        }
    }

    private function assertUniqueBusinessIdentity(
        int $formYear,
        string $titleTypeCodeUnique,
        string $formNumber,
        ?string $exceptCodeUnique = null,
    ): void {
        if ($this->forms->repo_businessIdentityExists(
            $formYear,
            $titleTypeCodeUnique,
            $formNumber,
            $exceptCodeUnique,
        )) {
            throw ValidationException::withMessages([
                'form_number' => 'Ce formulaire existe déjà pour ce type de titre et cette année.',
            ]);
        }
    }

    private function assertNotArchived(MissingFormModel $form): void
    {
        if ($form->status === FormStatus::Archived) {
            throw new LogicException('Un formulaire archivé ne peut plus être modifié.');
        }
    }
}
