<?php

namespace App\Repositories\Interfaces\Registry;

use App\Models\Registry\MissingFormImportModel;

interface MissingFormImportRepositoryInterface
{
    /** @param array{disk: string, file_path: string, original_file_name: string, size_bytes: int, created_by_user_code_unique: string} $attributes */
    public function repo_create(array $attributes): MissingFormImportModel;

    public function repo_findByCodeUnique(string $codeUnique): MissingFormImportModel;

    public function repo_markProcessing(MissingFormImportModel $import): void;

    public function repo_setTotalRows(MissingFormImportModel $import, int $totalRows): void;

    public function repo_updateValidationProgress(MissingFormImportModel $import, int $processedRows): void;

    public function repo_markCompleted(MissingFormImportModel $import, int $totalRows): void;

    /** @param list<string> $errors */
    public function repo_markFailed(MissingFormImportModel $import, int $processedRows, array $errors): void;
}
