<?php

namespace Artwork\Modules\User\Repositories;

use App\Models\User;
use Artwork\Core\Database\Repository\BaseRepository;
use Illuminate\Support\Collection;

class UserRepository extends BaseRepository
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
