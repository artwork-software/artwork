<?php

namespace Artwork\Modules\User\Services;

use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Repositories\ShiftRepository;
use Artwork\Modules\Shift\Services\ShiftService;
use Artwork\Modules\User\Http\Resources\UserShiftPlanResource;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Repositories\UserRepository;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class WorkingHourService
{
    public function __construct(
        private UserRepository $userRepository,
        private ShiftRepository $shiftRepository
    ) {
    }

    public function getUsersWithPlannedWorkingHours(
        Carbon $startDate,
        Carbon $endDate,
        string $desiredResourceClass,
        bool $addVacationsAndAvailabilities = false,
        User $currentUser = null,
        Collection $shiftCollection = null
    ): array {
        $usersWithPlannedWorkingHours = [];

        /** @var User $user */
        foreach ($this->userRepository->getWorkers() as $user) {
            /** @var JsonResource $desiredResourceClass */
            $desiredUserResource = $desiredResourceClass::make($user);
            if ($desiredUserResource instanceof UserShiftPlanResource) {
                $desiredUserResource->setStartDate($startDate)->setEndDate($endDate);
            }
            if ($shiftCollection) {
                $shifts = $shiftCollection->filter(function (Shift $shift) use ($user) {
                    return $shift->users->contains($user);
                })->all();
                $plannedWorkingHours = $this->calculatePlannedWorkingHours($shifts);
                $weeklyWorkingHours = $this->calculateWeeklyWorkingHoursByUserAndShifts(
                    $user,
                    $shifts,
                    $startDate,
                    $endDate
                );
            } else {
                $plannedWorkingHours = $this->plannedWorkingHoursForUser($user, $startDate, $endDate);
                $weeklyWorkingHours = $this->calculateWeeklyWorkingHoursByUser($user, $startDate, $endDate);
            }
            $userData = [
                'user' => $desiredUserResource->resolve(),
                'plannedWorkingHours' => $plannedWorkingHours,
                'expectedWorkingHours' => ($user->weekly_working_hours / 7) * ($startDate->diffInDays($endDate) + 1),
                'dayServices' => $user->dayServices?->groupBy('pivot.date'),
                'is_freelancer' => $user->getAttribute('is_freelancer'),
            ];

            $userData['weeklyWorkingHours'] = $weeklyWorkingHours;
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
        if ($currentUser && $currentUser->getAttribute('shift_plan_user_sort_by')) {
            usort($usersWithPlannedWorkingHours, static function ($a, $b) use ($currentUser) {
                return match ($currentUser->getAttribute('shift_plan_user_sort_by')) {
                    'ALPHABETICALLY_ASCENDING_FIRST_NAME' =>
                    strcmp($a['user']['first_name'], $b['user']['first_name']),
                    'ALPHABETICALLY_DESCENDING_FIRST_NAME' =>
                    strcmp($b['user']['first_name'], $a['user']['first_name']),
                    'ALPHABETICALLY_ASCENDING_LAST_NAME' =>
                    strcmp($a['user']['last_name'], $b['user']['last_name']),
                    'ALPHABETICALLY_DESCENDING_LAST_NAME' =>
                    strcmp($b['user']['last_name'], $a['user']['last_name']),
                    default => 0,
                };
            });
        }

        // calculate the working hours for each calendar week ($startDate - $endDate) and add it to the user data
        return $usersWithPlannedWorkingHours;
    }

    public function calculateWeeklyWorkingHoursByUser(
        User $user,
        Carbon|string $startDate,
        Carbon|string $endDate
    ): array {
        return $this->calculateWeeklyWorkingHours(
            $user,
            $this->plannedWorkingHoursForUser(
                $user,
                $startDate,
                $endDate
            ),
            Carbon::parse($startDate),
            Carbon::parse($endDate)
        );
    }

    public function calculateWeeklyWorkingHoursByUserAndShifts(
        User $worker,
        array $shifts,
        Carbon|string $startDate,
        Carbon|string $endDate
    ): array {
        return $this->calculateWeeklyWorkingHours(
            $worker,
            $this->calculatePlannedWorkingHours($shifts),
            Carbon::parse($startDate),
            Carbon::parse($endDate)
        );
    }

    private function calculateWeeklyWorkingHours(
        User $user,
        int|float $plannedWorkingHours,
        Carbon $startDate,
        Carbon $endDate
    ): array {
        $period = Carbon::parse($startDate)->toPeriod($endDate);

        $weeklyWorkingHours = [];

        foreach ($period as $week) {
            $workingHours = $plannedWorkingHours - $user->weekly_working_hours;
            $weeklyWorkingHours[$week->format('W')] = $workingHours;
        }

        return $weeklyWorkingHours;
    }

    public function plannedWorkingHoursForUser(User $user, Carbon|string $startDate, Carbon|string $endDate): float|int
    {
        $shiftsInDateRange = array_filter(
            $user->getAttribute('shifts')->all(),
            function (Shift $shift) use ($startDate, $endDate): bool {
                return
                    //start date between
                    (
                        $shift->getAttribute('start_date') >= $startDate &&
                        $shift->getAttribute('start_date') <= $endDate
                    ) ||
                    //end date between
                    (
                        $shift->getAttribute('end_date') >= $startDate &&
                        $shift->getAttribute('start_date') <= $endDate
                        //overlapping
                    ) || (
                        $shift->getAttribute('start_date') < $startDate &&
                        $shift->getAttribute('end_date') > $endDate
                    );
            }
        );

        return $this->calculatePlannedWorkingHours($shiftsInDateRange);
    }

    private function calculatePlannedWorkingHours(array $shiftsInDateRange): float|int
    {
        $plannedWorkingHours = 0;

        foreach ($shiftsInDateRange as $shift) {
            $shiftStart = $shift->start_date->format('Y-m-d') . ' ' . $shift->start; // Parse the start time
            $shiftEnd = $shift->end_date->format('Y-m-d') . ' ' . $shift->end;    // Parse the end time
            $breakMinutes = $shift->break_minutes;

            $shiftStart = Carbon::parse($shiftStart);
            $shiftEnd = Carbon::parse($shiftEnd);

            $shiftDuration = ($shiftEnd->diffInRealMinutes($shiftStart) - $breakMinutes) / 60;
            $plannedWorkingHours += $shiftDuration;
        }
        return $plannedWorkingHours;
    }
}
