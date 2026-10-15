<?php

namespace App\Contracts\Organization;

use App\Models\Organization\ServiceModel;
use Illuminate\Database\Eloquent\Collection;

interface OrganizationDirectoryServiceInterface
{
    /** @return Collection<int, ServiceModel> */
    public function srv_activeServices(): Collection;

    public function srv_findActiveService(string $codeUnique): ?ServiceModel;
}
