<?php

namespace App\Contracts\Registry;

use App\Models\Registry\SearchLocationModel;
use App\Models\Registry\TitleTypeModel;
use Illuminate\Database\Eloquent\Collection;

interface RegistryReferenceReadServiceInterface
{
    /** @return Collection<int, TitleTypeModel> */
    public function srv_titleTypes(): Collection;

    /** @return Collection<int, SearchLocationModel> */
    public function srv_searchLocations(): Collection;

    /** @return Collection<int, SearchLocationModel> */
    public function srv_activeSearchLocations(): Collection;
}
