<?php

namespace App\Contracts\Audit;

use App\Dto\Audit\AuditRequestContextDTO;

interface AuditContextProviderInterface
{
    public function context(): AuditRequestContextDTO;
}
