<?php

namespace App\Http\Controllers\Administration;

use App\Dto\Administration\UpdateRolePermissionsDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Administration\UpdateRolePermissionsFormRequest;
use App\Models\Administration\RoleModel;
use App\Services\Administration\RolePermissionManagementService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final class RolePermissionController extends Controller
{
    public function __construct(
        private readonly RolePermissionManagementService $rolePermissions,
    ) {}

    public function ctrl_index(): View
    {
        return view(
            'administration.roles.index',
            $this->rolePermissions->srv_index(),
        );
    }

    public function ctrl_update(
        UpdateRolePermissionsFormRequest $request,
        RoleModel $role,
    ): RedirectResponse {
        $this->rolePermissions->srv_update(
            $role,
            UpdateRolePermissionsDTO::fromValidated($request->validated()),
            $request->user(),
        );

        return redirect(route('roles.index').'#role-'.$role->uuid)
            ->with('success', "Les permissions du rôle « {$role->role_name} » ont été mises à jour.");
    }
}
