<?php

namespace App\Services\Audit;

use App\Contracts\Audit\AuditActivityReadServiceInterface;
use App\Repositories\Interfaces\Audit\AuditRepositoryInterface;
use Illuminate\Support\Collection;

final class AuditActivityReadService implements AuditActivityReadServiceInterface
{
    public function __construct(private readonly AuditRepositoryInterface $audit) {}

    public function srv_recentOperationalActivities(?string $actorCodeUnique = null, int $limit = 8): Collection
    {
        return $this->audit->repo_recentOperationalActivities($actorCodeUnique, $limit);
    }
}
