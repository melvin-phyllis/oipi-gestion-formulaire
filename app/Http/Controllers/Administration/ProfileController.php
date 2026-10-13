<?php

namespace App\Http\Controllers\Administration;

use App\Dto\Administration\UpdateProfileDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Administration\UpdateProfileFormRequest;
use App\Models\Administration\UserModel;
use App\Services\Administration\ProfileService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class ProfileController extends Controller
{
    public function __construct(private readonly ProfileService $profiles) {}

    public function ctrl_edit(Request $request): View
    {
        $user = $request->user();
        abort_unless($user instanceof UserModel, 403);

        $user->loadMissing(['role', 'service.directorate']);

        return view('profile.edit', [
            'user' => $user,
        ]);
    }

    public function ctrl_update(UpdateProfileFormRequest $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof UserModel, 403);

        $this->profiles->srv_update(
            $user,
            UpdateProfileDTO::fromValidated($request->validated()),
        );

        return back()->with('success', 'Votre profil a été mis à jour.');
    }
}
