<?php

namespace App\Http\Controllers\Administration;

use App\Dto\Administration\CreateUserDTO;
use App\Dto\Administration\ResetUserPasswordDTO;
use App\Dto\Administration\UpdateUserDTO;
use App\Dto\Administration\UpdateUserStatusDTO;
use App\Dto\Administration\UserFiltersDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Administration\GenerateUsernameFormRequest;
use App\Http\Requests\Administration\ListUsersFormRequest;
use App\Http\Requests\Administration\ResetUserPasswordFormRequest;
use App\Http\Requests\Administration\StoreUserFormRequest;
use App\Http\Requests\Administration\UpdateUserFormRequest;
use App\Http\Requests\Administration\UpdateUserStatusFormRequest;
use App\Models\Administration\UserModel;
use App\Services\Administration\UserManagementService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

final class UserController extends Controller
{
    public function __construct(
        private readonly UserManagementService $userManagement,
    ) {}

    public function ctrl_index(ListUsersFormRequest $request): View
    {
        return view(
            'administration.users.index',
            $this->userManagement->srv_list(
                UserFiltersDTO::fromValidated($request->validated()),
            ),
        );
    }

    public function ctrl_create(): View
    {
        return view(
            'administration.users.create',
            $this->userManagement->srv_formOptions(),
        );
    }

    public function ctrl_generateUsername(GenerateUsernameFormRequest $request): JsonResponse
    {
        $validated = $request->validated();

        return response()->json([
            'username' => $this->userManagement->srv_generateUsername(
                $validated['first_names'],
                $validated['last_name'],
            ),
        ]);
    }

    public function ctrl_store(StoreUserFormRequest $request): RedirectResponse
    {
        $result = $this->userManagement->srv_create(
            CreateUserDTO::fromValidated($request->validated()),
            $request->user(),
        );

        return redirect()
            ->route('users.edit', $result->user)
            ->with('success', 'Le compte utilisateur a été créé. Communiquez le mot de passe temporaire de manière sécurisée.')
            ->with('temporary_password', $result->temporaryPassword);
    }

    public function ctrl_edit(UserModel $user): View
    {
        return view(
            'administration.users.edit',
            $this->userManagement->srv_formOptions() + ['managedUser' => $user],
        );
    }

    public function ctrl_update(
        UpdateUserFormRequest $request,
        UserModel $user,
    ): RedirectResponse {
        $this->userManagement->srv_update(
            $user,
            UpdateUserDTO::fromValidated($request->validated()),
            $request->user(),
        );

        return redirect()
            ->route('users.edit', $user)
            ->with('success', 'Le compte utilisateur a été mis à jour.');
    }

    public function ctrl_updateStatus(
        UpdateUserStatusFormRequest $request,
        UserModel $user,
    ): RedirectResponse {
        $updatedUser = $this->userManagement->srv_updateStatus(
            $user,
            UpdateUserStatusDTO::fromValidated($request->validated()),
            $request->user(),
        );

        return back()->with(
            'success',
            $updatedUser->is_active
                ? 'Le compte utilisateur a été activé.'
                : 'Le compte utilisateur a été désactivé.',
        );
    }

    public function ctrl_resetPassword(
        ResetUserPasswordFormRequest $request,
        UserModel $user,
    ): RedirectResponse {
        $temporaryPassword = $this->userManagement->srv_resetPassword(
            $user,
            ResetUserPasswordDTO::fromValidated($request->validated()),
            $request->user(),
        );

        return redirect()
            ->route('users.edit', $user)
            ->with('success', 'Le mot de passe a été réinitialisé et les anciennes sessions ont été invalidées.')
            ->with('temporary_password', $temporaryPassword);
    }
}
