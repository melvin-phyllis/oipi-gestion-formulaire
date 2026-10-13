<?php

namespace App\Repositories\Organization;

use App\Dto\Organization\SaveDirectorateDTO;
use App\Dto\Organization\SaveServiceDTO;
use App\Models\Organization\DirectorateModel;
use App\Models\Organization\ServiceModel;
use App\Repositories\Interfaces\Organization\OrganizationDirectoryRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class OrganizationDirectoryRepository implements OrganizationDirectoryRepositoryInterface
{
    public function repo_directoratesWithServices(): Collection
    {
        return DirectorateModel::withTrashed()
            ->with(['services' => fn ($query) => $query
                ->withTrashed()
                ->orderBy('display_order')
                ->orderBy('service_name')])
            ->orderByRaw('deleted_at IS NOT NULL')
            ->orderBy('display_order')
            ->orderBy('directorate_name')
            ->get();
    }

    public function repo_activeDirectorates(): Collection
    {
        return DirectorateModel::query()
            ->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('directorate_name')
            ->get();
    }

    public function repo_activeServices(): Collection
    {
        return ServiceModel::query()
            ->with('directorate')
            ->where('is_active', true)
            ->whereHas('directorate', fn (Builder $query): Builder => $query
                ->where('is_active', true))
            ->orderBy('display_order')
            ->orderBy('service_name')
            ->get();
    }

    public function repo_findActiveService(string $serviceCodeUnique): ?ServiceModel
    {
        return ServiceModel::query()
            ->with('directorate')
            ->where('service_code_unique', $serviceCodeUnique)
            ->where('is_active', true)
            ->whereHas('directorate', fn (Builder $query): Builder => $query
                ->where('is_active', true))
            ->first();
    }

    public function repo_findActiveDirectorate(string $directorateCodeUnique): ?DirectorateModel
    {
        return DirectorateModel::query()
            ->where('directorate_code_unique', $directorateCodeUnique)
            ->where('is_active', true)
            ->first();
    }

    public function repo_createDirectorate(SaveDirectorateDTO $data): DirectorateModel
    {
        return DirectorateModel::query()->create([
            'directorate_code' => $data->directorateCode,
            'directorate_name' => $data->directorateName,
            'display_order' => $data->displayOrder,
            'is_active' => true,
        ]);
    }

    public function repo_updateDirectorate(
        DirectorateModel $directorate,
        SaveDirectorateDTO $data,
    ): DirectorateModel {
        $directorate->update([
            'directorate_code' => $data->directorateCode,
            'directorate_name' => $data->directorateName,
            'display_order' => $data->displayOrder,
        ]);

        return $directorate->refresh();
    }

    public function repo_setDirectorateActive(
        DirectorateModel $directorate,
        bool $isActive,
    ): DirectorateModel {
        $directorate->is_active = $isActive;
        $directorate->save();

        return $directorate->refresh();
    }

    public function repo_hasActiveServices(DirectorateModel $directorate): bool
    {
        return $directorate->services()
            ->where('is_active', true)
            ->exists();
    }

    public function repo_hasServices(DirectorateModel $directorate): bool
    {
        return $directorate->services()->exists();
    }

    public function repo_deleteDirectorate(DirectorateModel $directorate): void
    {
        $directorate->is_active = false;
        $directorate->save();
        $directorate->delete();
    }

    public function repo_restoreDirectorate(DirectorateModel $directorate): DirectorateModel
    {
        $directorate->restore();
        $directorate->update(['is_active' => false]);

        return $directorate->refresh();
    }

    public function repo_createService(SaveServiceDTO $data): ServiceModel
    {
        return ServiceModel::query()->create([
            'directorate_code_unique' => $data->directorateCodeUnique,
            'service_code' => $data->serviceCode,
            'abbreviation' => $data->abbreviation,
            'service_name' => $data->serviceName,
            'display_order' => $data->displayOrder,
            'is_active' => true,
        ])->load('directorate');
    }

    public function repo_updateService(
        ServiceModel $service,
        SaveServiceDTO $data,
    ): ServiceModel {
        $service->update([
            'directorate_code_unique' => $data->directorateCodeUnique,
            'service_code' => $data->serviceCode,
            'abbreviation' => $data->abbreviation,
            'service_name' => $data->serviceName,
            'display_order' => $data->displayOrder,
        ]);

        return $service->refresh()->load('directorate');
    }

    public function repo_setServiceActive(
        ServiceModel $service,
        bool $isActive,
    ): ServiceModel {
        $service->is_active = $isActive;
        $service->save();

        return $service->refresh();
    }

    public function repo_countActiveUsers(ServiceModel $service): int
    {
        return $service->users()
            ->where('is_active', true)
            ->count();
    }

    public function repo_hasUsers(ServiceModel $service): bool
    {
        return $service->users()->withTrashed()->exists();
    }

    public function repo_deleteService(ServiceModel $service): void
    {
        $service->is_active = false;
        $service->save();
        $service->delete();
    }

    public function repo_restoreService(ServiceModel $service): ServiceModel
    {
        $service->restore();
        $service->update(['is_active' => false]);

        return $service->refresh();
    }

    public function repo_directorateExists(string $directorateCodeUnique): bool
    {
        return DirectorateModel::query()
            ->where('directorate_code_unique', $directorateCodeUnique)
            ->exists();
    }
}
