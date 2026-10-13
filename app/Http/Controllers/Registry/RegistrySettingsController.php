<?php

namespace App\Http\Controllers\Registry;

use App\Dto\Registry\SaveSearchLocationDTO;
use App\Dto\Registry\SaveTitleTypeDTO;
use App\Dto\Registry\UpdateReferenceStatusDTO;
use App\Enums\Registry\FormStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Registry\ManageReferenceLifecycleFormRequest;
use App\Http\Requests\Registry\SaveSearchLocationFormRequest;
use App\Http\Requests\Registry\SaveTitleTypeFormRequest;
use App\Http\Requests\Registry\UpdateReferenceStatusFormRequest;
use App\Models\Registry\SearchLocationModel;
use App\Models\Registry\TitleTypeModel;
use App\Services\Registry\RegistryReferenceService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final class RegistrySettingsController extends Controller
{
    public function __construct(private readonly RegistryReferenceService $references) {}

    public function ctrl_index(): View
    {
        return view('registry.settings.index', $this->references->srv_lists() + [
            'statuses' => FormStatus::cases(),
        ]);
    }

    public function ctrl_storeTitleType(SaveTitleTypeFormRequest $request): RedirectResponse
    {
        $this->references->srv_createTitleType(
            SaveTitleTypeDTO::fromValidated($request->validated()),
            $request->user(),
        );

        return $this->backTo('title-types', 'Le type de titre a été ajouté.');
    }

    public function ctrl_updateTitleType(SaveTitleTypeFormRequest $request, TitleTypeModel $titleType): RedirectResponse
    {
        $this->references->srv_updateTitleType(
            $titleType,
            SaveTitleTypeDTO::fromValidated($request->validated()),
            $request->user(),
        );

        return $this->backTo('title-types', 'Le type de titre a été mis à jour.');
    }

    public function ctrl_updateTitleTypeStatus(UpdateReferenceStatusFormRequest $request, TitleTypeModel $titleType): RedirectResponse
    {
        $item = $this->references->srv_setTitleTypeStatus(
            $titleType,
            UpdateReferenceStatusDTO::fromValidated($request->validated()),
            $request->user(),
        );

        return $this->backTo('title-types', $item->is_active ? 'Le type de titre a été activé.' : 'Le type de titre a été désactivé.');
    }

    public function ctrl_destroyTitleType(ManageReferenceLifecycleFormRequest $request, TitleTypeModel $titleType): RedirectResponse
    {
        $this->references->srv_deleteTitleType($titleType, $request->user());

        return $this->backTo('title-types', 'Le type de titre a été supprimé.');
    }

    public function ctrl_restoreTitleType(ManageReferenceLifecycleFormRequest $request, TitleTypeModel $titleType): RedirectResponse
    {
        $this->references->srv_restoreTitleType($titleType, $request->user());

        return $this->backTo('title-types', 'Le type de titre a été restauré. Il reste inactif jusqu’à sa réactivation.');
    }

    public function ctrl_storeSearchLocation(SaveSearchLocationFormRequest $request): RedirectResponse
    {
        $this->references->srv_createSearchLocation(
            SaveSearchLocationDTO::fromValidated($request->validated()),
            $request->user(),
        );

        return $this->backTo('search-locations', 'La localisation a été ajoutée.');
    }

    public function ctrl_updateSearchLocation(SaveSearchLocationFormRequest $request, SearchLocationModel $searchLocation): RedirectResponse
    {
        $this->references->srv_updateSearchLocation(
            $searchLocation,
            SaveSearchLocationDTO::fromValidated($request->validated()),
            $request->user(),
        );

        return $this->backTo('search-locations', 'La localisation a été mise à jour.');
    }

    public function ctrl_updateSearchLocationStatus(UpdateReferenceStatusFormRequest $request, SearchLocationModel $searchLocation): RedirectResponse
    {
        $item = $this->references->srv_setSearchLocationStatus(
            $searchLocation,
            UpdateReferenceStatusDTO::fromValidated($request->validated()),
            $request->user(),
        );

        return $this->backTo('search-locations', $item->is_active ? 'La localisation a été activée.' : 'La localisation a été désactivée.');
    }

    public function ctrl_destroySearchLocation(ManageReferenceLifecycleFormRequest $request, SearchLocationModel $searchLocation): RedirectResponse
    {
        $this->references->srv_deleteSearchLocation($searchLocation, $request->user());

        return $this->backTo('search-locations', 'La localisation a été supprimée.');
    }

    public function ctrl_restoreSearchLocation(ManageReferenceLifecycleFormRequest $request, SearchLocationModel $searchLocation): RedirectResponse
    {
        $this->references->srv_restoreSearchLocation($searchLocation, $request->user());

        return $this->backTo('search-locations', 'La localisation a été restaurée. Elle reste inactive jusqu’à sa réactivation.');
    }

    private function backTo(string $fragment, string $message): RedirectResponse
    {
        return redirect(route('registry.settings.index').'#'.$fragment)
            ->with('success', $message)
            ->with('active_registry_tab', $fragment);
    }
}
