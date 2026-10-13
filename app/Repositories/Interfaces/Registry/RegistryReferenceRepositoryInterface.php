<?php

namespace App\Repositories\Interfaces\Registry;

use App\Dto\Registry\SaveSearchLocationDTO;
use App\Dto\Registry\SaveTitleTypeDTO;
use App\Models\Registry\SearchLocationModel;
use App\Models\Registry\TitleTypeModel;
use Illuminate\Database\Eloquent\Collection;

interface RegistryReferenceRepositoryInterface
{
    /** @return Collection<int, TitleTypeModel> */
    public function repo_titleTypes(): Collection;

    /** @return Collection<int, SearchLocationModel> */
    public function repo_searchLocations(): Collection;

    /** @return Collection<int, TitleTypeModel> */
    public function repo_availableTitleTypes(?string $currentCodeUnique = null): Collection;

    /** @return Collection<int, SearchLocationModel> */
    public function repo_activeSearchLocations(): Collection;

    public function repo_createTitleType(SaveTitleTypeDTO $data): TitleTypeModel;

    public function repo_updateTitleType(TitleTypeModel $titleType, SaveTitleTypeDTO $data): TitleTypeModel;

    public function repo_setTitleTypeActive(TitleTypeModel $titleType, bool $isActive): TitleTypeModel;

    public function repo_titleTypeIsUsed(TitleTypeModel $titleType): bool;

    public function repo_deleteTitleType(TitleTypeModel $titleType): void;

    public function repo_restoreTitleType(TitleTypeModel $titleType): TitleTypeModel;

    public function repo_createSearchLocation(SaveSearchLocationDTO $data): SearchLocationModel;

    public function repo_updateSearchLocation(SearchLocationModel $location, SaveSearchLocationDTO $data): SearchLocationModel;

    public function repo_setSearchLocationActive(SearchLocationModel $location, bool $isActive): SearchLocationModel;

    public function repo_searchLocationIsUsed(SearchLocationModel $location): bool;

    public function repo_deleteSearchLocation(SearchLocationModel $location): void;

    public function repo_restoreSearchLocation(SearchLocationModel $location): SearchLocationModel;
}
