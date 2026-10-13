<?php

namespace App\Repositories\Interfaces\Backup;

use App\Dto\Backup\BackupFileDTO;
use App\Dto\Backup\BackupFiltersDTO;
use App\Models\Backup\DatabaseBackupModel;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface DatabaseBackupRepositoryInterface
{
    public function repo_create(BackupFileDTO $data): DatabaseBackupModel;

    /** @return LengthAwarePaginator<int, DatabaseBackupModel> */
    public function repo_paginate(BackupFiltersDTO $filters, int $perPage = 20): LengthAwarePaginator;

    public function repo_markDeleted(DatabaseBackupModel $backup, ?string $actorCodeUnique): void;

    /** @return Collection<int, DatabaseBackupModel> */
    public function repo_expiredAutomatic(CarbonImmutable $cutoff): Collection;

    public function repo_rehydrate(DatabaseBackupModel $backup): DatabaseBackupModel;
}
