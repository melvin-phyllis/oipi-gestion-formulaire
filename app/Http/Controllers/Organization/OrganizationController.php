<?php

namespace App\Http\Controllers\Organization;

use App\Dto\Organization\SaveDirectorateDTO;
use App\Dto\Organization\SaveServiceDTO;
use App\Dto\Organization\UpdateOrganizationStatusDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\ManageOrganizationItemLifecycleFormRequest;
use App\Http\Requests\Organization\StoreDirectorateFormRequest;
use App\Http\Requests\Organization\StoreServiceFormRequest;
use App\Http\Requests\Organization\UpdateDirectorateFormRequest;
use App\Http\Requests\Organization\UpdateOrganizationStatusFormRequest;
use App\Http\Requests\Organization\UpdateServiceFormRequest;
use App\Models\Organization\DirectorateModel;
use App\Models\Organization\ServiceModel;
use App\Services\Organization\OrganizationManagementService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final class OrganizationController extends Controller
{
    public function __construct(
        private readonly OrganizationManagementService $organization,
    ) {}

    public function ctrl_index(): View
    {
        return view('organization.index', [
            'directorates' => $this->organization->srv_directoratesWithServices(),
        ]);
    }

    public function ctrl_createDirectorate(): View
    {
        return view('organization.directorates.create');
    }

    public function ctrl_storeDirectorate(
        StoreDirectorateFormRequest $request,
    ): RedirectResponse {
        $directorate = $this->organization->srv_createDirectorate(
            SaveDirectorateDTO::fromValidated($request->validated()),
            $request->user(),
        );

        return redirect(route('organization.index').'#directorate-'.$directorate->uuid)
            ->with('success', 'La direction a été ajoutée.');
    }

    public function ctrl_editDirectorate(DirectorateModel $directorate): View
    {
        return view('organization.directorates.edit', compact('directorate'));
    }

    public function ctrl_updateDirectorate(
        UpdateDirectorateFormRequest $request,
        DirectorateModel $directorate,
    ): RedirectResponse {
        $this->organization->srv_updateDirectorate(
            $directorate,
            SaveDirectorateDTO::fromValidated($request->validated()),
            $request->user(),
        );

        return redirect(route('organization.index').'#directorate-'.$directorate->uuid)
            ->with('success', 'La direction a été mise à jour.');
    }

    public function ctrl_updateDirectorateStatus(
        UpdateOrganizationStatusFormRequest $request,
        DirectorateModel $directorate,
    ): RedirectResponse {
        $updatedDirectorate = $this->organization->srv_updateDirectorateStatus(
            $directorate,
            UpdateOrganizationStatusDTO::fromValidated($request->validated()),
            $request->user(),
        );

        return back()->with(
            'success',
            $updatedDirectorate->is_active
                ? 'La direction a été activée.'
                : 'La direction a été désactivée.',
        );
    }

    public function ctrl_destroyDirectorate(
        ManageOrganizationItemLifecycleFormRequest $request,
        DirectorateModel $directorate,
    ): RedirectResponse {
        $this->organization->srv_deleteDirectorate($directorate, $request->user());

        return redirect()->route('organization.index')
            ->with('success', 'La direction a été supprimée.');
    }

    public function ctrl_restoreDirectorate(
        ManageOrganizationItemLifecycleFormRequest $request,
        DirectorateModel $directorate,
    ): RedirectResponse {
        $directorate = $this->organization->srv_restoreDirectorate($directorate, $request->user());

        return redirect(route('organization.index').'#directorate-'.$directorate->uuid)
            ->with('success', 'La direction a été restaurée. Elle reste inactive jusqu’à sa réactivation.');
    }

    public function ctrl_createService(DirectorateModel $directorate): View
    {
        abort_unless($directorate->is_active, 404);

        return view('organization.services.create', [
            'directorates' => $this->organization->srv_activeDirectorates(),
            'selectedDirectorate' => $directorate,
        ]);
    }

    public function ctrl_storeService(StoreServiceFormRequest $request): RedirectResponse
    {
        $service = $this->organization->srv_createService(
            SaveServiceDTO::fromValidated($request->validated()),
            $request->user(),
        );

        return redirect(route('organization.index').'#directorate-'.$service->directorate->uuid)
            ->with('success', 'Le service a été ajouté.');
    }

    public function ctrl_editService(ServiceModel $service): View
    {
        return view('organization.services.edit', [
            'managedService' => $service,
            'directorates' => $this->organization->srv_activeDirectorates(),
        ]);
    }

    public function ctrl_updateService(
        UpdateServiceFormRequest $request,
        ServiceModel $service,
    ): RedirectResponse {
        $updatedService = $this->organization->srv_updateService(
            $service,
            SaveServiceDTO::fromValidated($request->validated()),
            $request->user(),
        );

        return redirect(route('organization.index').'#directorate-'.$updatedService->directorate->uuid)
            ->with('success', 'Le service a été mis à jour.');
    }

    public function ctrl_updateServiceStatus(
        UpdateOrganizationStatusFormRequest $request,
        ServiceModel $service,
    ): RedirectResponse {
        $updatedService = $this->organization->srv_updateServiceStatus(
            $service,
            UpdateOrganizationStatusDTO::fromValidated($request->validated()),
            $request->user(),
        );

        return back()->with(
            'success',
            $updatedService->is_active
                ? 'Le service a été activé.'
                : 'Le service a été désactivé.',
        );
    }

    public function ctrl_destroyService(
        ManageOrganizationItemLifecycleFormRequest $request,
        ServiceModel $service,
    ): RedirectResponse {
        $directorateUuid = $service->directorate?->uuid;

        $this->organization->srv_deleteService($service, $request->user());

        return redirect(route('organization.index').($directorateUuid ? '#directorate-'.$directorateUuid : ''))
            ->with('success', 'Le service a été supprimé.');
    }

    public function ctrl_restoreService(
        ManageOrganizationItemLifecycleFormRequest $request,
        ServiceModel $service,
    ): RedirectResponse {
        $service = $this->organization->srv_restoreService($service, $request->user());

        return redirect(route('organization.index').'#directorate-'.$service->directorate->uuid)
            ->with('success', 'Le service a été restauré. Il reste inactif jusqu’à sa réactivation.');
    }
}
