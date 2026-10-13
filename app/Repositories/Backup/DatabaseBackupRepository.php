<?php

namespace App\Repositories\Backup;

use App\Dto\Backup\BackupFileDTO;
use App\Dto\Backup\BackupFiltersDTO;
use App\Enums\Backup\BackupType;
use App\Models\Administration\UserModel;
use App\Models\Backup\DatabaseBackupModel;
use App\Repositories\Interfaces\Backup\DatabaseBackupRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class DatabaseBackupRepository implements DatabaseBackupRepositoryInterface
{
    public function repo_create(BackupFileDTO $data): DatabaseBackupModel
    {
        return DatabaseBackupModel::query()->create([
            'backup_type' => $data->type,
            'disk' => $data->disk,
            'file_path' => $data->filePath,
            'file_name' => $data->fileName,
            'size_bytes' => $data->sizeBytes,
            'sha256' => $data->sha256,
            'database_name' => $data->databaseName,
            'created_by_user_code_unique' => $data->creatorCodeUnique,
            'creator_name' => $data->creatorName,
        ]);
    }

    public function repo_paginate(BackupFiltersDTO $filters, int $perPage = 20): LengthAwarePaginator
    {
        return DatabaseBackupModel::query()
            ->when(
                $filters->type,
                fn (Builder $query, BackupType $type): Builder => $query->where('backup_type', $type->value),
            )
            ->when(
                $filters->createdFrom,
                fn (Builder $query, $date): Builder => $query->where('created_at', '>=', $date),
            )
            ->when(
                $filters->createdTo,
                fn (Builder $query, $date): Builder => $query->where('created_at', '<=', $date),
            )
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function repo_markDeleted(DatabaseBackupModel $backup, ?string $actorCodeUnique): void
    {
        $backup->deleted_by_user_code_unique = $actorCodeUnique;
        $backup->save();
        $backup->delete();
    }

    public function repo_expiredAutomatic(CarbonImmutable $cutoff): Collection
    {
        return DatabaseBackupModel::query()
            ->whereIn('backup_type', [BackupType::Scheduled->value, BackupType::PreRestore->value])
            ->where('created_at', '<', $cutoff)
            ->orderBy('created_at')
            ->get();
    }

    public function repo_rehydrate(DatabaseBackupModel $backup): DatabaseBackupModel
    {
        $creatorCodeUnique = $backup->created_by_user_code_unique;
        if ($creatorCodeUnique !== null && ! UserModel::withTrashed()
            ->where('user_code_unique', $creatorCodeUnique)
            ->exists()) {
            $creatorCodeUnique = null;
        }

        $record = DatabaseBackupModel::withTrashed()->firstOrNew([
            'database_backup_code_unique' => $backup->database_backup_code_unique,
        ]);
        $record->forceFill([
            'uuid' => $backup->uuid,
            'backup_type' => $backup->backup_type,
            'disk' => $backup->disk,
            'file_path' => $backup->file_path,
            'file_name' => $backup->file_name,
            'size_bytes' => $backup->size_bytes,
            'sha256' => $backup->sha256,
            'database_name' => $backup->database_name,
            'created_by_user_code_unique' => $creatorCodeUnique,
            'creator_name' => $backup->creator_name,
            'created_at' => $backup->created_at,
            'updated_at' => $backup->updated_at,
            'deleted_by_user_code_unique' => null,
            'deleted_at' => null,
        ]);
        $record->save();

        return $record;
    }
}
