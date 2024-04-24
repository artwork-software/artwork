<?php

namespace Artwork\Modules\User\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Collection;

readonly class UserRepository extends BaseRepository
{
    /**
     * @return Collection
     */
    public function getAllUsers(): Collection
    {
        return User::all();
    }

    /**
     * @param int $userId
     * @return User
     */
    public function findUser(int $userId): User
    {
        return User::find($userId);
    }
}
