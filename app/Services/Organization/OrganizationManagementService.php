<?php

namespace App\Services\Organization;

use App\Dto\Organization\SaveDirectorateDTO;
use App\Dto\Organization\SaveServiceDTO;
use App\Dto\Organization\UpdateOrganizationStatusDTO;
use App\Events\Organization\DirectorateCreated;
use App\Events\Organization\DirectorateDeleted;
use App\Events\Organization\DirectorateRestored;
use App\Events\Organization\DirectorateStatusChanged;
use App\Events\Organization\DirectorateUpdated;
use App\Events\Organization\ServiceCreated;
use App\Events\Organization\ServiceDeleted;
use App\Events\Organization\ServiceRestored;
use App\Events\Organization\ServiceStatusChanged;
use App\Events\Organization\ServiceUpdated;
use App\Models\Administration\UserModel;
use App\Models\Organization\DirectorateModel;
use App\Models\Organization\ServiceModel;
use App\Repositories\Interfaces\Organization\OrganizationDirectoryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class OrganizationManagementService
{
    public function __construct(
        private readonly OrganizationDirectoryRepositoryInterface $organization,
    ) {}

    /** @return Collection<int, DirectorateModel> */
    public function srv_directoratesWithServices(): Collection
    {
        return $this->organization->repo_directoratesWithServices();
    }

    /** @return Collection<int, DirectorateModel> */
    public function srv_activeDirectorates(): Collection
    {
        return $this->organization->repo_activeDirectorates();
    }

    public function srv_createDirectorate(
        SaveDirectorateDTO $data,
        UserModel $actor,
    ): DirectorateModel {
        $directorate = DB::transaction(
            fn (): DirectorateModel => $this->organization
                ->repo_createDirectorate($data),
        );

        DirectorateCreated::dispatch($directorate->uuid, $actor->uuid);

        return $directorate;
    }

    public function srv_updateDirectorate(
        DirectorateModel $directorate,
        SaveDirectorateDTO $data,
        UserModel $actor,
    ): DirectorateModel {
        $updatedDirectorate = DB::transaction(
            fn (): DirectorateModel => $this->organization
                ->repo_updateDirectorate($directorate, $data),
        );

        DirectorateUpdated::dispatch($updatedDirectorate->uuid, $actor->uuid);

        return $updatedDirectorate;
    }

    /** @throws ValidationException */
    public function srv_updateDirectorateStatus(
        DirectorateModel $directorate,
        UpdateOrganizationStatusDTO $data,
        UserModel $actor,
    ): DirectorateModel {
        if ($directorate->is_active === $data->isActive) {
            return $directorate;
        }

        if (
            ! $data->isActive
            && $this->organization->repo_hasActiveServices($directorate)
        ) {
            throw ValidationException::withMessages([
                'is_active' => 'Désactivez d’abord tous les services actifs de cette direction.',
            ]);
        }

        $updatedDirectorate = DB::transaction(
            fn (): DirectorateModel => $this->organization
                ->repo_setDirectorateActive($directorate, $data->isActive),
        );

        DirectorateStatusChanged::dispatch(
            $updatedDirectorate->uuid,
            $actor->uuid,
            $updatedDirectorate->is_active,
        );

        return $updatedDirectorate;
    }

    /** @throws ValidationException */
    public function srv_deleteDirectorate(
        DirectorateModel $directorate,
        UserModel $actor,
    ): void {
        if ($this->organization->repo_hasServices($directorate)) {
            throw ValidationException::withMessages([
                'directorate' => 'Supprimez ou déplacez d’abord tous les services rattachés à cette direction.',
            ]);
        }

        DB::transaction(
            fn () => $this->organization->repo_deleteDirectorate($directorate),
        );

        DirectorateDeleted::dispatch($directorate->uuid, $actor->uuid);
    }

    /** @throws ValidationException */
    public function srv_restoreDirectorate(
        DirectorateModel $directorate,
        UserModel $actor,
    ): DirectorateModel {
        if (! $directorate->trashed()) {
            throw ValidationException::withMessages([
                'directorate' => 'Cette direction n’est pas supprimée.',
            ]);
        }

        $directorate = DB::transaction(
            fn (): DirectorateModel => $this->organization->repo_restoreDirectorate($directorate),
        );

        DirectorateRestored::dispatch($directorate->uuid, $actor->uuid);

        return $directorate;
    }

    /** @throws ValidationException */
    public function srv_createService(
        SaveServiceDTO $data,
        UserModel $actor,
    ): ServiceModel {
        $this->assertActiveDirectorate($data->directorateCodeUnique);

        $service = DB::transaction(
            fn (): ServiceModel => $this->organization->repo_createService($data),
        );

        ServiceCreated::dispatch($service->uuid, $actor->uuid);

        return $service;
    }

    /** @throws ValidationException */
    public function srv_updateService(
        ServiceModel $service,
        SaveServiceDTO $data,
        UserModel $actor,
    ): ServiceModel {
        $this->assertActiveDirectorate($data->directorateCodeUnique);

        $updatedService = DB::transaction(
            fn (): ServiceModel => $this->organization
                ->repo_updateService($service, $data),
        );

        ServiceUpdated::dispatch($updatedService->uuid, $actor->uuid);

        return $updatedService;
    }

    /** @throws ValidationException */
    public function srv_updateServiceStatus(
        ServiceModel $service,
        UpdateOrganizationStatusDTO $data,
        UserModel $actor,
    ): ServiceModel {
        if ($service->is_active === $data->isActive) {
            return $service;
        }

        if (
            ! $data->isActive
            && $this->organization->repo_countActiveUsers($service) > 0
        ) {
            throw ValidationException::withMessages([
                'is_active' => 'Réaffectez ou désactivez d’abord les utilisateurs actifs de ce service.',
            ]);
        }

        if ($data->isActive) {
            $this->assertActiveDirectorate($service->directorate_code_unique);
        }

        $updatedService = DB::transaction(
            fn (): ServiceModel => $this->organization
                ->repo_setServiceActive($service, $data->isActive),
        );

        ServiceStatusChanged::dispatch(
            $updatedService->uuid,
            $actor->uuid,
            $updatedService->is_active,
        );

        return $updatedService;
    }

    /** @throws ValidationException */
    public function srv_deleteService(
        ServiceModel $service,
        UserModel $actor,
    ): void {
        if ($this->organization->repo_hasUsers($service)) {
            throw ValidationException::withMessages([
                'service' => 'Réaffectez d’abord tous les comptes rattachés à ce service, y compris les comptes inactifs.',
            ]);
        }

        DB::transaction(
            fn () => $this->organization->repo_deleteService($service),
        );

        ServiceDeleted::dispatch($service->uuid, $actor->uuid);
    }

    /** @throws ValidationException */
    public function srv_restoreService(
        ServiceModel $service,
        UserModel $actor,
    ): ServiceModel {
        if (! $service->trashed()) {
            throw ValidationException::withMessages([
                'service' => 'Ce service n’est pas supprimé.',
            ]);
        }
        if (! $this->organization->repo_directorateExists($service->directorate_code_unique)) {
            throw ValidationException::withMessages([
                'service' => 'Restaurez d’abord la direction associée à ce service.',
            ]);
        }

        $service = DB::transaction(
            fn (): ServiceModel => $this->organization->repo_restoreService($service),
        );

        ServiceRestored::dispatch($service->uuid, $actor->uuid);

        return $service;
    }

    /** @throws ValidationException */
    private function assertActiveDirectorate(string $directorateCodeUnique): void
    {
        if (! $this->organization->repo_findActiveDirectorate($directorateCodeUnique)) {
            throw ValidationException::withMessages([
                'directorate_code_unique' => 'La direction sélectionnée est indisponible.',
            ]);
        }
    }
}
