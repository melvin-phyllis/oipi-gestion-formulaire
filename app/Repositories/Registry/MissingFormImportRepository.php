<?php

namespace App\Repositories\Registry;

use App\Enums\Registry\RegistryImportStatus;
use App\Models\Registry\MissingFormImportModel;
use App\Repositories\Interfaces\Registry\MissingFormImportRepositoryInterface;

final class MissingFormImportRepository implements MissingFormImportRepositoryInterface
{
    public function repo_create(array $attributes): MissingFormImportModel
    {
        return MissingFormImportModel::query()->create($attributes);
    }

    public function repo_findByCodeUnique(string $codeUnique): MissingFormImportModel
    {
        return MissingFormImportModel::query()
            ->where('missing_form_import_code_unique', $codeUnique)
            ->firstOrFail();
    }

    public function repo_markProcessing(MissingFormImportModel $import): void
    {
        $import->update([
            'status' => RegistryImportStatus::Processing,
            'total_rows' => 0,
            'processed_rows' => 0,
            'imported_rows' => 0,
            'error_count' => 0,
            'errors' => null,
            'started_at' => now(),
            'completed_at' => null,
            'failed_at' => null,
        ]);
    }

    public function repo_updateValidationProgress(MissingFormImportModel $import, int $processedRows): void
    {
        $import->update([
            'processed_rows' => $processedRows,
        ]);
    }

    public function repo_setTotalRows(MissingFormImportModel $import, int $totalRows): void
    {
        $import->update(['total_rows' => $totalRows]);
    }

    public function repo_markCompleted(MissingFormImportModel $import, int $totalRows): void
    {
        $import->update([
            'status' => RegistryImportStatus::Completed,
            'total_rows' => $totalRows,
            'processed_rows' => $totalRows,
            'imported_rows' => $totalRows,
            'error_count' => 0,
            'errors' => null,
            'completed_at' => now(),
            'failed_at' => null,
        ]);
    }

    public function repo_markFailed(MissingFormImportModel $import, int $processedRows, array $errors): void
    {
        $import->update([
            'status' => RegistryImportStatus::Failed,
            'total_rows' => $processedRows,
            'processed_rows' => $processedRows,
            'imported_rows' => 0,
            'error_count' => count($errors),
            'errors' => array_slice($errors, 0, 50),
            'failed_at' => now(),
            'completed_at' => null,
        ]);
    }
}
