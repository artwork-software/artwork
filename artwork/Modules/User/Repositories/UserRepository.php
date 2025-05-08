<?php

namespace Artwork\Modules\User\Repositories;

use Artwork\Core\Database\Models\CanSubstituteBaseModel;
use Artwork\Core\Database\Models\Model;
use Artwork\Core\Database\Models\Pivot;
use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection as SupportCollection;

class UserRepository extends BaseRepository
{
    public function __construct(private readonly User $user)
    {
    }

    public function getNewModelInstance(): Model|Pivot|DatabaseNotification|CanSubstituteBaseModel
    {
        return $this->user->newInstance();
    }

    public function getNewModelQuery(): BaseBuilder|Builder
    {
        return $this->user->newModelQuery();
    }

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

    /**
     * @throws ModelNotFoundException<Model>
     */
    public function findUserOrFail(int $userId): User
    {
        /** @var User $user */
        $user = User::query()->findOrFail($userId);

        return $user;
    }

    public function getWorkers(): Collection
    {
        return User::query()->canWorkShifts()->with(
            'dayServices',
            'shifts',
            'shifts.event',
            'shifts.event.room',
            'departments',
            'shifts.shiftsQualifications',
            'shiftQualifications',
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

    public function searchUsers(string $search): SupportCollection
    {
        return User::search($search)
            ->get()
            ->map(fn(User $user) => [
                'id' => $user->getAttribute('id'),
                'name' => $user->getAttribute('full_name'),
                'first_name' => $user->getAttribute('first_name'),
                'last_name' => $user->getAttribute('last_name'),
                'email' => $user->getAttribute('email'),
                'project_manager_permission' => $user->getHasProjectManagerPermission(),
                'profile_photo_url' => $user->getAttribute('profile_photo_url'),
                'manager_type' => $user::class,
                'chat_public_key' => $user->getAttribute('chat_public_key'),
                'use_chat' => $user->getAttribute('use_chat'),
            ]);
    }

    public function atAGlanceEnabled(User $user): bool
    {
        return $user->getAttribute('at_a_glance');
    }

    public function getNotReadOfNotificationTypeNotSentInSummaryForUser(
        User $user,
        string $notificationConstValue
    ): Collection {
        return $user->notifications()
            ->whereNull("read_at")
            ->whereJsonContains("data->type", $notificationConstValue)
            ->where("sent_in_summary", false)
            ->get();
    }

    public function syncDepartments(User $user, array $departmentIds): User
    {
        $user->departments()->sync($departmentIds);

        return $user;
    }
}
