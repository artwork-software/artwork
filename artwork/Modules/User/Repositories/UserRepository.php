<?php

namespace Artwork\Modules\User\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

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
     * @return User|null
     */
    public function findUser(int $userId): User|null
    {
        return User::find($userId);
    }

    public function findUserOrFail(int $userId): User
    {
        return User::findOrFail($userId);
    }

    public function getWorkers(): Collection
    {
        return User::query()->canWorkShifts()->get();
    }

    public function getAvailabilitiesBetweenDatesGroupedByFormattedDate(
        int|User $user,
        Carbon $startDate,
        Carbon $endDate
    ): Collection {
        if (!$user instanceof User) {
            $user = $this->findUserOrFail($user);
        }

        return $user->availabilities()->betweenDates($startDate, $endDate)->get()->groupBy('formatted_date');
    }
}
