<?php

namespace App\Contracts\Audit;

use App\Models\Audit\AuditLogModel;
use Illuminate\Support\Collection;

interface AuditActivityReadServiceInterface
{
    /** @return Collection<int, AuditLogModel> */
    public function srv_recentOperationalActivities(?string $actorCodeUnique = null, int $limit = 8): Collection;
}
