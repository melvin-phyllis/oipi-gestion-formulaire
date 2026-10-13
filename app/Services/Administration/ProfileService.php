<?php

namespace App\Services\Administration;

use App\Dto\Administration\UpdateProfileDTO;
use App\Events\Administration\ProfileUpdated;
use App\Models\Administration\UserModel;
use App\Repositories\Interfaces\Administration\UserRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class ProfileService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
    ) {}

    public function srv_update(
        UserModel $user,
        UpdateProfileDTO $data,
    ): UserModel {
        $changedFields = $this->changedFields($user, $data);
        if ($changedFields === []) {
            return $user->loadMissing(['role', 'service.directorate']);
        }

        if (in_array('email', $changedFields, true)
            && (! is_string($data->currentPassword)
                || ! Hash::check($data->currentPassword, $user->password))) {
            throw ValidationException::withMessages([
                'current_password' => 'Le mot de passe actuel est incorrect.',
            ]);
        }

        $updatedUser = DB::transaction(
            fn (): UserModel => $this->users->repo_updateProfile($user, $data),
        );

        ProfileUpdated::dispatch($updatedUser->uuid, $updatedUser->uuid, $changedFields);

        return $updatedUser;
    }

    /** @return list<string> */
    private function changedFields(UserModel $user, UpdateProfileDTO $data): array
    {
        $values = [
            'last_name' => $data->lastName,
            'first_names' => $data->firstNames,
            'email' => $data->email,
            'phone' => $data->phone,
        ];

        return array_values(array_keys(array_filter(
            $values,
            fn (mixed $value, string $field): bool => $user->getAttribute($field) !== $value,
            ARRAY_FILTER_USE_BOTH,
        )));
    }
}
