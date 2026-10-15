<?php

namespace App\Services\Organization;

use App\Contracts\Organization\OrganizationDirectoryServiceInterface;
use App\Models\Organization\ServiceModel;
use App\Repositories\Interfaces\Organization\OrganizationDirectoryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

final class OrganizationDirectoryService implements OrganizationDirectoryServiceInterface
{
    public function __construct(private readonly OrganizationDirectoryRepositoryInterface $organization) {}

    /** @return Collection<int, ServiceModel> */
    public function srv_activeServices(): Collection
    {
        return $this->organization->repo_activeServices();
    }

    public function srv_findActiveService(string $codeUnique): ?ServiceModel
    {
        return $this->organization->repo_findActiveService($codeUnique);
    }
}
