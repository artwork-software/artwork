<?php

namespace Artwork\Modules\User\Services;

use Artwork\Modules\User\Http\Resources\UserShiftPlanResource;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Repositories\UserRepository;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class UserService
{
    /**
     * @param UserRepository $userRepository
     */
    public function __construct(private readonly UserRepository $userRepository)
    {
    }

    public function searchUsers(string $search): \Illuminate\Support\Collection
    {
        return $this->userRepository->searchUsers($search);
    }

    public function getAuthUser(bool $needCalendarAbo = false): ?User
    {
        /** @var User $user */
        $user = Auth::user();
        if ($needCalendarAbo && !$user->calendarAbo) {
            $user->load(['calendarAbo']);
        }

        return $user;
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

    /**
     * @return array<string, mixed>
     */
    public function getUsersWithPlannedWorkingHours(
        Carbon $startDate,
        Carbon $endDate,
        string $desiredResourceClass,
        bool $addVacationsAndAvailabilities = false
    ): array {
        $usersWithPlannedWorkingHours = [];

        /** @var User $user */
        foreach ($this->userRepository->getWorkers() as $user) {
            /** @var JsonResource $desiredResourceClass */
            $desiredUserResource = $desiredResourceClass::make($user);

            if ($desiredUserResource instanceof UserShiftPlanResource) {
                $desiredUserResource->setStartDate($startDate)->setEndDate($endDate);
            }

            $userData = [
                'user' => $desiredUserResource->resolve(),
                'plannedWorkingHours' => $user->plannedWorkingHours($startDate, $endDate),
                'expectedWorkingHours' => ($user->weekly_working_hours / 7) * ($startDate->diffInDays($endDate) + 1),
                'dayServices' => $user->dayServices?->groupBy('pivot.date'),
            ];

            $userData['weeklyWorkingHours'] = $this->calculateWeeklyWorkingHours($user, $startDate, $endDate);

            if ($addVacationsAndAvailabilities) {
                $userData['vacations'] = $user->getVacationDays();
                $userData['availabilities'] = $this->userRepository
                    ->getAvailabilitiesBetweenDatesGroupedByFormattedDate(
                        $user,
                        $startDate,
                        $endDate
                    );
            }

            $usersWithPlannedWorkingHours[] = $userData;
        }

        // calculate the working hours for each calendar week ($startDate - $endDate) and add it to the user data
        return $usersWithPlannedWorkingHours;
    }

    /**
     * @return array<string, float|int>
     */
    public function calculateWeeklyWorkingHours(User $user, Carbon $startDate, Carbon $endDate): array
    {
        // first create a carbon period for the given date range
        $period = Carbon::parse($startDate)->toPeriod($endDate);

        $weeklyWorkingHours = [];

        // iterate over each week and calculate the working hours
        foreach ($period as $week) {
            $startDate = $week->copy()->startOfWeek();
            $endDate = $week->copy()->endOfWeek();
            $workingHours = $user->plannedWorkingHours(
                $startDate,
                $endDate
            ) - $user->weekly_working_hours;
            $weeklyWorkingHours[$week->format('W')] = $workingHours;
        }

        return $weeklyWorkingHours;
    }

    public function getAuthUserCrafts(): Collection
    {
        return $this->getAuthUser()->crafts;
    }

    public function getUserVacationsByDateOrderedByDateAsc(int|User $user, Carbon $selectedDate): Collection
    {
        return $this->userRepository->getUserVacationsByDateOrderedByDateAsc($user, $selectedDate);
    }

    public function getUserAvailabilitiesByDateOrderedByDateAsc(int|User $user, Carbon $selectedDate): Collection
    {
        return $this->userRepository->getUserAvailabilitiesByDateOrderedByDateAsc(
            $user,
            $selectedDate
        );
    }

    public function getUserShiftsOrderedByStartAscending(int|User $user): Collection
    {
        return $this->userRepository->getShiftsOrderedByStartAscending($user);
    }

    /**
     * @return array<int, Carbon>
     */
    public function getUserCalendarFilterDatesOrDefault(?User $user = null): array
    {
        if (!$user instanceof User) {
            $user = $this->getAuthUser();
        }

        $userCalendarFilter = $user->getAttribute('calendar_filter');
        $hasUserCalendarFilterDates = !is_null($userCalendarFilter?->getAttribute('start_date')) &&
            !is_null($userCalendarFilter?->getAttribute('end_date'));

        $startDate = $hasUserCalendarFilterDates ?
            Carbon::create($userCalendarFilter->getAttribute('start_date'))->startOfDay() :
            Carbon::now()->startOfDay();
        $endDate = $hasUserCalendarFilterDates ?
            Carbon::create($userCalendarFilter->getAttribute('end_date'))->endOfDay() :
            Carbon::now()->addWeeks()->endOfDay();

        return [$startDate, $endDate];
    }

    /**
     * @return array<int, Carbon>
     */
    public function getUserShiftCalendarFilterDatesOrDefault(User $user): array
    {
        $userShiftCalendarFilter = $user->shift_calendar_filter;

        $hasUserShiftCalendarFilterDates = !is_null($userShiftCalendarFilter?->start_date) &&
            !is_null($userShiftCalendarFilter?->end_date);
        $startDate = $hasUserShiftCalendarFilterDates ?
            Carbon::create($userShiftCalendarFilter->start_date)->startOfDay() :
            Carbon::now()->startOfDay();
        $endDate = $hasUserShiftCalendarFilterDates ?
            Carbon::create($userShiftCalendarFilter->end_date)->endOfDay() :
            Carbon::now()->addWeeks()->endOfDay();

        return [$startDate, $endDate];
    }

    public function getAdminUser(): User
    {
        return $this->userRepository->getAdminUser();
    }

    public function atAGlanceEnabled(User|int|null $user = null): bool
    {
        return $this->userRepository->atAGlanceEnabled(
            is_int($user) ?
                $this->findUser($user) :
                ($user instanceof User ?
                    $user :
                        $this->getAuthUser())
        );
    }

    public function update(int|User $user, array $attributes): User
    {
        $this->userRepository->update(
            !$user instanceof User ? ($user = $this->userRepository->findUserOrFail($user)) : $user,
            $attributes
        );

        return $user;
    }

    public function getNotReadOfNotificationTypeNotSentInSummaryForUser(
        User $user,
        string $notificationConstValue
    ): Collection {
        return $this->userRepository->getNotReadOfNotificationTypeNotSentInSummaryForUser(
            $user,
            $notificationConstValue
        );
    }
}
