<?php

namespace Artwork\Modules\User\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

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
     * @return User|null
     */
    public function findUser(int $userId): User|null
    {
        return User::find($userId);
    }

    public function findWorker(int $workerId): User|null
    {
        return User::query()->canWorkShifts()->where('id', $workerId)->first();
    }

    public function findUserOrFail(int $userId): User
    {
        return User::findOrFail($userId);
    }

    public function getWorkers(): Collection
    {
        return User::query()->canWorkShifts()->with(
            'dayServices',
            'shifts',
            'shifts.event',
            'shifts.event.room'
        )->get();
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

    public function getUserVacationsByDateOrderedByDateAsc(User|int $user, Carbon $selectedDate): Collection
    {
        if (!$user instanceof User) {
            $user = $this->findUserOrFail($user);
        }

        return $user->vacations()
            ->byDate($selectedDate)
            ->orderedByDate()
            ->get();
    }

    public function getUserAvailabilitiesByDateOrderedByDateAsc(User|int $user, Carbon $selectedDate): Collection
    {
        if (!$user instanceof User) {
            $user = $this->findUserOrFail($user);
        }

        return $user->availabilities()
            ->byDate($selectedDate)
            ->orderedByDate()
            ->get();
    }

    public function getShiftsOrderedByStartAscending(int|User $user): Collection
    {
        if (!$user instanceof User) {
            $user = $this->findUser($user);
        }

        return $user
            ->shifts()
            ->with(['event', 'event.project', 'event.room'])
            ->orderedByStart()
            ->get();
    }

    public function getAdminUser(): User
    {
        return User::role(RoleEnum::ARTWORK_ADMIN->value)->first();
    }

    public function searchUsers(string $search): \Illuminate\Support\Collection
    {
        return User::search($search)->get()->map(fn(User $user) => [
            'id' => $user->id,
            'name' => $user->full_name,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'project_manager_permission' => $user->getHasProjectManagerPermission(),
            'profile_photo_url' => $user->profile_photo_url,
        ]);
    }

    public function atAGlanceEnabled(User $user): bool
    {
        return $user->getAttribute('at_a_glance');
    }
}
