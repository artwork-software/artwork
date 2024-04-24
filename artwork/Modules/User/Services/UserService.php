<?php

namespace Artwork\Modules\User\Services;

use App\Models\User;
use Artwork\Modules\User\Repositories\UserRepository;
use Illuminate\Support\Collection;

readonly class UserService
{
    /**
     * @param UserRepository $userRepository
     */
    public function __construct(private UserRepository $userRepository)
    {
    }

    /**
     * @return Collection
     */
    public function getAllUsers(): Collection
    {
        return $this->userRepository->getAllUsers();
    }

    /**
     * @param int $id
     * @return User
     */
    public function findUser(int $id): User
    {
        return $this->userRepository->findUser($id);
    }
}
