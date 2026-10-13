<?php

namespace App\Repositories\Interfaces\Registry;

use App\Dto\Registry\CreateMissingFormDTO;
use App\Dto\Registry\MissingFormFiltersDTO;
use App\Dto\Registry\UpdateMissingFormDTO;
use App\Enums\Registry\FormStatus;
use App\Models\Registry\MissingFormModel;
use App\Models\Registry\TitleTypeModel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface MissingFormRepositoryInterface
{
    /** @return LengthAwarePaginator<int, MissingFormModel> */
    public function repo_paginate(MissingFormFiltersDTO $filters, int $perPage = 20): LengthAwarePaginator;

    public function repo_findActiveTitleType(string $codeUnique): ?TitleTypeModel;

    public function repo_loadForDisplay(MissingFormModel $form): MissingFormModel;

    public function repo_hasActiveMissions(string $formCodeUnique): bool;

    public function repo_businessIdentityExists(
        int $formYear,
        string $titleTypeCodeUnique,
        string $formNumber,
        ?string $exceptCodeUnique = null,
    ): bool;

    /**
     * @param  list<array{title_type_code_unique: string, form_year: int, form_number: string}>  $rows
     * @return list<string>
     */
    public function repo_existingBusinessIdentityKeys(array $rows): array;

    /**
     * @param  list<array{title_type_code_unique: string, form_year: int, form_number: string, priority: string}>  $rows
     */
    public function repo_bulkCreateImported(
        array $rows,
        string $actorCodeUnique,
        string $importCodeUnique,
    ): void;

    public function repo_finalizeImportedRegistryNumbers(string $importCodeUnique): void;

    public function repo_create(CreateMissingFormDTO $data, string $actorCodeUnique): MissingFormModel;

    public function repo_updateMetadata(
        MissingFormModel $form,
        UpdateMissingFormDTO $data,
        string $actorCodeUnique,
    ): MissingFormModel;

    public function repo_archive(
        MissingFormModel $form,
        FormStatus $previousStatus,
        string $reason,
        string $actorCodeUnique,
    ): MissingFormModel;

    public function repo_restore(
        MissingFormModel $form,
        FormStatus $restoredStatus,
        string $actorCodeUnique,
    ): MissingFormModel;
}
