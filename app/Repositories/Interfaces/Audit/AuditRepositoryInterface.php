<?php

namespace App\Repositories\Interfaces\Audit;

use App\Dto\Audit\AuditFiltersDTO;
use App\Dto\Audit\RecordAuditDTO;
use App\Models\Administration\UserModel;
use App\Models\Audit\AuditLogModel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface AuditRepositoryInterface
{
    public function repo_create(RecordAuditDTO $data): AuditLogModel;

    /** @return LengthAwarePaginator<int, AuditLogModel> */
    public function repo_paginate(AuditFiltersDTO $filters, int $perPage = 25): LengthAwarePaginator;

    /** @return Collection<int, object> */
    public function repo_actorOptions(): Collection;

    /** @return Collection<int, AuditLogModel> */
    public function repo_recentOperationalActivities(
        ?string $actorUserCodeUnique = null,
        int $limit = 8,
    ): Collection;

    public function repo_findUserByUuid(string $uuid): ?UserModel;

    /** @return array{codeUnique: string|null, label: string|null} */
    public function repo_resolveSubject(string $type, string $uuid): array;
}
