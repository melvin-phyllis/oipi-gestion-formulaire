<?php

namespace App\Services\Registry;

use App\Dto\Registry\SaveSearchLocationDTO;
use App\Dto\Registry\SaveTitleTypeDTO;
use App\Dto\Registry\UpdateReferenceStatusDTO;
use App\Events\Registry\ReferenceItemCreated;
use App\Events\Registry\ReferenceItemDeleted;
use App\Events\Registry\ReferenceItemRestored;
use App\Events\Registry\ReferenceItemStatusChanged;
use App\Events\Registry\ReferenceItemUpdated;
use App\Models\Administration\UserModel;
use App\Models\Registry\SearchLocationModel;
use App\Models\Registry\TitleTypeModel;
use App\Repositories\Interfaces\Registry\RegistryReferenceRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class RegistryReferenceService
{
    public function __construct(private readonly RegistryReferenceRepositoryInterface $references) {}

    /** @return array{titleTypes: Collection<int, TitleTypeModel>, searchLocations: Collection<int, SearchLocationModel>} */
    public function srv_lists(): array
    {
        return [
            'titleTypes' => $this->references->repo_titleTypes(),
            'searchLocations' => $this->references->repo_searchLocations(),
        ];
    }

    public function srv_createTitleType(SaveTitleTypeDTO $data, UserModel $actor): TitleTypeModel
    {
        $item = DB::transaction(fn () => $this->references->repo_createTitleType($data));
        ReferenceItemCreated::dispatch('title_type', $item->uuid, $actor->uuid);

        return $item;
    }

    public function srv_updateTitleType(TitleTypeModel $item, SaveTitleTypeDTO $data, UserModel $actor): TitleTypeModel
    {
        $item = DB::transaction(fn () => $this->references->repo_updateTitleType($item, $data));
        ReferenceItemUpdated::dispatch('title_type', $item->uuid, $actor->uuid);

        return $item;
    }

    public function srv_setTitleTypeStatus(TitleTypeModel $item, UpdateReferenceStatusDTO $data, UserModel $actor): TitleTypeModel
    {
        $item = DB::transaction(fn () => $this->references->repo_setTitleTypeActive($item, $data->isActive));
        ReferenceItemStatusChanged::dispatch('title_type', $item->uuid, $actor->uuid, $item->is_active);

        return $item;
    }

    /** @throws ValidationException */
    public function srv_deleteTitleType(TitleTypeModel $item, UserModel $actor): void
    {
        if ($this->references->repo_titleTypeIsUsed($item)) {
            throw ValidationException::withMessages([
                'title_type' => 'Ce type de titre est utilisé par au moins un formulaire et ne peut pas être supprimé.',
            ]);
        }

        DB::transaction(fn () => $this->references->repo_deleteTitleType($item));
        ReferenceItemDeleted::dispatch('title_type', $item->uuid, $actor->uuid);
    }

    /** @throws ValidationException */
    public function srv_restoreTitleType(TitleTypeModel $item, UserModel $actor): TitleTypeModel
    {
        if (! $item->trashed()) {
            throw ValidationException::withMessages([
                'title_type' => 'Ce type de titre n’est pas supprimé.',
            ]);
        }

        $item = DB::transaction(fn () => $this->references->repo_restoreTitleType($item));
        ReferenceItemRestored::dispatch('title_type', $item->uuid, $actor->uuid);

        return $item;
    }

    public function srv_createSearchLocation(SaveSearchLocationDTO $data, UserModel $actor): SearchLocationModel
    {
        $item = DB::transaction(fn () => $this->references->repo_createSearchLocation($data));
        ReferenceItemCreated::dispatch('search_location', $item->uuid, $actor->uuid);

        return $item;
    }

    public function srv_updateSearchLocation(SearchLocationModel $item, SaveSearchLocationDTO $data, UserModel $actor): SearchLocationModel
    {
        $item = DB::transaction(fn () => $this->references->repo_updateSearchLocation($item, $data));
        ReferenceItemUpdated::dispatch('search_location', $item->uuid, $actor->uuid);

        return $item;
    }

    public function srv_setSearchLocationStatus(SearchLocationModel $item, UpdateReferenceStatusDTO $data, UserModel $actor): SearchLocationModel
    {
        $item = DB::transaction(fn () => $this->references->repo_setSearchLocationActive($item, $data->isActive));
        ReferenceItemStatusChanged::dispatch('search_location', $item->uuid, $actor->uuid, $item->is_active);

        return $item;
    }

    /** @throws ValidationException */
    public function srv_deleteSearchLocation(SearchLocationModel $item, UserModel $actor): void
    {
        if ($this->references->repo_searchLocationIsUsed($item)) {
            throw ValidationException::withMessages([
                'search_location' => 'Cette localisation est utilisée par un formulaire ou une recherche et ne peut pas être supprimée.',
            ]);
        }

        DB::transaction(fn () => $this->references->repo_deleteSearchLocation($item));
        ReferenceItemDeleted::dispatch('search_location', $item->uuid, $actor->uuid);
    }

    /** @throws ValidationException */
    public function srv_restoreSearchLocation(SearchLocationModel $item, UserModel $actor): SearchLocationModel
    {
        if (! $item->trashed()) {
            throw ValidationException::withMessages([
                'search_location' => 'Cette localisation n’est pas supprimée.',
            ]);
        }

        $item = DB::transaction(fn () => $this->references->repo_restoreSearchLocation($item));
        ReferenceItemRestored::dispatch('search_location', $item->uuid, $actor->uuid);

        return $item;
    }
}
