<?php

namespace App\Repositories\Registry;

use App\Dto\Registry\SaveSearchLocationDTO;
use App\Dto\Registry\SaveTitleTypeDTO;
use App\Models\Mission\SearchHistoryModel;
use App\Models\Mission\SearchMissionModel;
use App\Models\Registry\DirectFoundReportModel;
use App\Models\Registry\MissingFormModel;
use App\Models\Registry\SearchLocationModel;
use App\Models\Registry\TitleTypeModel;
use App\Repositories\Interfaces\Registry\RegistryReferenceRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

final class RegistryReferenceRepository implements RegistryReferenceRepositoryInterface
{
    public function repo_titleTypes(): Collection
    {
        return TitleTypeModel::withTrashed()
            ->orderByRaw('deleted_at IS NOT NULL')
            ->orderBy('display_order')
            ->orderBy('title_type_name')
            ->get();
    }

    public function repo_searchLocations(): Collection
    {
        return SearchLocationModel::withTrashed()
            ->orderByRaw('deleted_at IS NOT NULL')
            ->orderBy('display_order')
            ->orderBy('location_name')
            ->get();
    }

    public function repo_availableTitleTypes(?string $currentCodeUnique = null): Collection
    {
        return TitleTypeModel::query()
            ->where(function (Builder $query) use ($currentCodeUnique): void {
                $query->where('is_active', true)
                    ->when(
                        $currentCodeUnique,
                        fn (Builder $query, string $code): Builder => $query
                            ->orWhere('title_type_code_unique', $code),
                    );
            })
            ->orderBy('display_order')
            ->orderBy('title_type_name')
            ->get();
    }

    public function repo_activeSearchLocations(): Collection
    {
        return SearchLocationModel::query()
            ->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('location_name')
            ->get();
    }

    public function repo_createTitleType(SaveTitleTypeDTO $data): TitleTypeModel
    {
        return TitleTypeModel::query()->create([
            'title_type_code' => $data->titleTypeCode,
            'title_type_name' => $data->titleTypeName,
            'display_order' => $data->displayOrder,
            'is_active' => true,
        ]);
    }

    public function repo_updateTitleType(TitleTypeModel $titleType, SaveTitleTypeDTO $data): TitleTypeModel
    {
        $titleType->update([
            'title_type_code' => $data->titleTypeCode,
            'title_type_name' => $data->titleTypeName,
            'display_order' => $data->displayOrder,
        ]);

        return $titleType->refresh();
    }

    public function repo_setTitleTypeActive(TitleTypeModel $titleType, bool $isActive): TitleTypeModel
    {
        $titleType->update(['is_active' => $isActive]);

        return $titleType->refresh();
    }

    public function repo_titleTypeIsUsed(TitleTypeModel $titleType): bool
    {
        return MissingFormModel::withTrashed()
            ->where('title_type_code_unique', $titleType->title_type_code_unique)
            ->exists();
    }

    public function repo_deleteTitleType(TitleTypeModel $titleType): void
    {
        $titleType->update(['is_active' => false]);
        $titleType->delete();
    }

    public function repo_restoreTitleType(TitleTypeModel $titleType): TitleTypeModel
    {
        $titleType->restore();
        $titleType->update(['is_active' => false]);

        return $titleType->refresh();
    }

    public function repo_createSearchLocation(SaveSearchLocationDTO $data): SearchLocationModel
    {
        return SearchLocationModel::query()->create([
            'location_code' => $this->nextLocationCode($data->locationName),
            'location_name' => $data->locationName,
            'description' => $data->description,
            'display_order' => $data->displayOrder,
            'is_active' => true,
        ]);
    }

    public function repo_updateSearchLocation(SearchLocationModel $location, SaveSearchLocationDTO $data): SearchLocationModel
    {
        $location->update([
            'location_name' => $data->locationName,
            'description' => $data->description,
            'display_order' => $data->displayOrder,
        ]);

        return $location->refresh();
    }

    public function repo_setSearchLocationActive(SearchLocationModel $location, bool $isActive): SearchLocationModel
    {
        $location->update(['is_active' => $isActive]);

        return $location->refresh();
    }

    public function repo_searchLocationIsUsed(SearchLocationModel $location): bool
    {
        $code = $location->search_location_code_unique;

        return MissingFormModel::withTrashed()
            ->where('last_search_location_code_unique', $code)
            ->exists()
            || SearchMissionModel::withTrashed()
                ->where('search_location_code_unique', $code)
                ->exists()
            || SearchHistoryModel::query()
                ->where('search_location_code_unique', $code)
                ->exists()
            || DirectFoundReportModel::query()
                ->where('found_location_code_unique', $code)
                ->exists();
    }

    public function repo_deleteSearchLocation(SearchLocationModel $location): void
    {
        $location->update(['is_active' => false]);
        $location->delete();
    }

    public function repo_restoreSearchLocation(SearchLocationModel $location): SearchLocationModel
    {
        $location->restore();
        $location->update(['is_active' => false]);

        return $location->refresh();
    }

    private function nextLocationCode(string $name): string
    {
        $base = Str::upper(Str::slug($name, '_')) ?: 'LOCATION';
        $base = mb_substr($base, 0, 50);
        $code = $base;

        while (SearchLocationModel::withTrashed()->where('location_code', $code)->exists()) {
            $code = $base.'_'.Str::upper(Str::random(6));
        }

        return $code;
    }
}
