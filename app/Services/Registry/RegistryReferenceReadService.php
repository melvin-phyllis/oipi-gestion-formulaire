<?php

namespace App\Services\Registry;

use App\Contracts\Registry\RegistryReferenceReadServiceInterface;
use App\Repositories\Interfaces\Registry\RegistryReferenceRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

final class RegistryReferenceReadService implements RegistryReferenceReadServiceInterface
{
    public function __construct(private readonly RegistryReferenceRepositoryInterface $references) {}

    public function srv_titleTypes(): Collection
    {
        return $this->references->repo_titleTypes();
    }

    public function srv_searchLocations(): Collection
    {
        return $this->references->repo_searchLocations();
    }

    public function srv_activeSearchLocations(): Collection
    {
        return $this->references->repo_activeSearchLocations();
    }
}
