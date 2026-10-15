<?php

namespace App\Services\Administration;

use App\Contracts\Administration\UserDirectoryServiceInterface;
use App\Models\Administration\UserModel;
use App\Repositories\Interfaces\Administration\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

final class UserDirectoryService implements UserDirectoryServiceInterface
{
    public function __construct(private readonly UserRepositoryInterface $users) {}

    /** @return Collection<int, UserModel> */
    public function srv_activeUsers(): Collection
    {
        return $this->users->repo_activeUsers();
    }

    public function srv_findByCodeUnique(string $codeUnique): UserModel
    {
        return $this->users->repo_findByCodeUnique($codeUnique);
    }

    public function srv_loadRole(UserModel $user): UserModel
    {
        return $this->users->repo_loadRole($user);
    }
}
