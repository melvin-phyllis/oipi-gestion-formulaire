<?php

namespace App\Services\Audit;

use App\Dto\Audit\AuditFiltersDTO;
use App\Enums\Audit\AuditAction;
use App\Enums\Audit\AuditCategory;
use App\Models\Audit\AuditLogModel;
use App\Repositories\Interfaces\Audit\AuditRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final class AuditReadService
{
    public function __construct(private readonly AuditRepositoryInterface $audit) {}

    /**
     * @return array{
     *     logs: LengthAwarePaginator<int, AuditLogModel>,
     *     categories: array<int, AuditCategory>,
     *     actions: array<int, AuditAction>,
     *     actors: Collection<int, object>
     * }
     */
    public function srv_index(AuditFiltersDTO $filters): array
    {
        return [
            'logs' => $this->audit->repo_paginate($filters),
            'categories' => AuditCategory::cases(),
            'actions' => AuditAction::cases(),
            'actors' => $this->audit->repo_actorOptions(),
        ];
    }
}
