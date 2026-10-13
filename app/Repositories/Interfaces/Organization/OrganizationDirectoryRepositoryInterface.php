<?php

namespace App\Repositories\Interfaces\Organization;

use App\Dto\Organization\SaveDirectorateDTO;
use App\Dto\Organization\SaveServiceDTO;
use App\Models\Organization\DirectorateModel;
use App\Models\Organization\ServiceModel;
use Illuminate\Database\Eloquent\Collection;

interface OrganizationDirectoryRepositoryInterface
{
    /** @return Collection<int, DirectorateModel> */
    public function repo_directoratesWithServices(): Collection;

    /** @return Collection<int, DirectorateModel> */
    public function repo_activeDirectorates(): Collection;

    /** @return Collection<int, ServiceModel> */
    public function repo_activeServices(): Collection;

    public function repo_findActiveDirectorate(string $directorateCodeUnique): ?DirectorateModel;

    public function repo_findActiveService(string $serviceCodeUnique): ?ServiceModel;

    public function repo_createDirectorate(SaveDirectorateDTO $data): DirectorateModel;

    public function repo_updateDirectorate(
        DirectorateModel $directorate,
        SaveDirectorateDTO $data,
    ): DirectorateModel;

    public function repo_setDirectorateActive(
        DirectorateModel $directorate,
        bool $isActive,
    ): DirectorateModel;

    public function repo_hasServices(DirectorateModel $directorate): bool;

    public function repo_hasActiveServices(DirectorateModel $directorate): bool;

    public function repo_deleteDirectorate(DirectorateModel $directorate): void;

    public function repo_restoreDirectorate(DirectorateModel $directorate): DirectorateModel;

    public function repo_createService(SaveServiceDTO $data): ServiceModel;

    public function repo_updateService(
        ServiceModel $service,
        SaveServiceDTO $data,
    ): ServiceModel;

    public function repo_setServiceActive(
        ServiceModel $service,
        bool $isActive,
    ): ServiceModel;

    public function repo_hasUsers(ServiceModel $service): bool;

    public function repo_countActiveUsers(ServiceModel $service): int;

    public function repo_deleteService(ServiceModel $service): void;

    public function repo_restoreService(ServiceModel $service): ServiceModel;

    public function repo_directorateExists(string $directorateCodeUnique): bool;
}
