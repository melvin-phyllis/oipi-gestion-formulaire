<?php

namespace App\Contracts\Administration;

use App\Models\Administration\UserModel;
use Illuminate\Database\Eloquent\Collection;

interface UserDirectoryServiceInterface
{
    /** @return Collection<int, UserModel> */
    public function srv_activeUsers(): Collection;

    public function srv_findByCodeUnique(string $codeUnique): UserModel;

    public function srv_loadRole(UserModel $user): UserModel;
}
