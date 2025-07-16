<?php

namespace Artwork\Modules\User\Services;

use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\User\Http\Resources\UserShiftPlanResource;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Repositories\UserRepository;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class WorkingHourService
{
    public function __construct(
        private UserRepository $userRepository,
    ) {
    }

    public function calculateShiftTime(
        User|Freelancer|ServiceProvider $entity,
        Carbon $startDate,
        Carbon $endDate
    ): int {
        $shiftsInDateRange = array_filter(
            $entity->getAttribute('shifts')->all(),
            static function (Shift $shift) use ($startDate, $endDate): bool {
                return (
                    ($shift->getAttribute('start_date') >= $startDate &&
                        $shift->getAttribute('start_date') <= $endDate) ||
                    ($shift->getAttribute('end_date') >= $startDate &&
                        $shift->getAttribute('start_date') <= $endDate) ||
                    ($shift->getAttribute('start_date') < $startDate && $shift->getAttribute('end_date') > $endDate)
                );
            }
        );

        $intervals = [];

        foreach ($shiftsInDateRange as $shift) {
            $shiftStart = Carbon::parse($shift->start_date->format('Y-m-d') . ' ' . $shift->start);
            $shiftEnd = Carbon::parse($shift->end_date->format('Y-m-d') . ' ' . $shift->end);

            // Add the shift interval to the list
            $intervals[] = [$shiftStart, $shiftEnd];
        }

        // Merge overlapping intervals
        usort($intervals, fn($a, $b) => $a[0]->lt($b[0]) ? -1 : 1);
        $mergedIntervals = [];
        foreach ($intervals as $interval) {
            if (empty($mergedIntervals) || $mergedIntervals[count($mergedIntervals) - 1][1]->lt($interval[0])) {
                $mergedIntervals[] = $interval;
            } else {
                $mergedIntervals[count($mergedIntervals) - 1][1]
                    = $mergedIntervals[count($mergedIntervals) - 1][1]->max($interval[1]);
            }
        }

        // Calculate total working time
        $totalWorkingMinutes = 0;
        foreach ($mergedIntervals as $interval) {
            $totalWorkingMinutes += $interval[1]->diffInMinutes($interval[0]);
        }

        // Subtract break times
        foreach ($shiftsInDateRange as $shift) {
            $totalWorkingMinutes -= $shift->break_minutes;
        }

        return $totalWorkingMinutes;
    }

    public function convertMinutesInHours(int $minutes, bool $forcePositive = false): string
    {
        $absMinutes = abs($minutes);
        $hours = intdiv($absMinutes, 60);
        $remainingMinutes = $absMinutes % 60;
        $sign = (!$forcePositive && $minutes < 0) ? '-' : '';
        return sprintf('%s%dh %dm', $sign, $hours, $remainingMinutes);
    }


    /**
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param string $desiredResourceClass
     * @param bool $addVacationsAndAvailabilities
     * @param User|null $currentUser
     * @param Collection|null $shiftCollection
     * @return string<mixed>
     */
    //@todo: fix phpcs error - refactor function because complexity exceeds allowed maximum
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
    public function getUsersWithPlannedWorkingHours(
        Carbon $startDate,
        Carbon $endDate,
        string $desiredResourceClass,
        bool $addVacationsAndAvailabilities = false,
        User $currentUser = null
    ): array {
        $usersWithPlannedWorkingHours = [];

        /** @var User $user */
        foreach ($this->userRepository->getWorkers() as $user) {
            /** @var JsonResource $desiredResourceClass */
            $desiredUserResource = $desiredResourceClass::make($user);
            if ($desiredUserResource instanceof UserShiftPlanResource) {
                $desiredUserResource->setStartDate($startDate)->setEndDate($endDate);
            }

            $plannedWorkingHours = $this->convertMinutesInHours(
                $this->calculateShiftTime($user, $startDate, $endDate)
            );
            $weeklyWorkingHours = $this->calculateWeeklyWorkingHours(
                $user,
                $startDate,
                $endDate
            );

            $userData = [
                'user' => $desiredUserResource->resolve(),
                'plannedWorkingHours' => $plannedWorkingHours,
                'expectedWorkingHours' => $this->convertMinutesInHours(
                    $this->calculateExpectedMinutesBasedOnWorkPattern($user, $startDate, $endDate)
                ),
                'dayServices' => $user->dayServices?->groupBy('pivot.date'),
                'is_freelancer' => $user->getAttribute('is_freelancer'),
                'individual_times' => $user->individualTimes()->individualByDateRange($startDate, $endDate)->get(),
                'shift_comments' => $user->getShiftPlanCommentsForPeriod($startDate, $endDate),
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
        if ($currentUser && $currentUser->getAttribute('shift_plan_user_sort_by_id')) {
            usort($usersWithPlannedWorkingHours, static function ($a, $b) use ($currentUser) {
                return match ($currentUser->getAttribute('shift_plan_user_sort_by_id')) {
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
        return $usersWithPlannedWorkingHours;
    }

    /**
     * @param User $user
     * @param int|float $plannedWorkingHours
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return string<mixed>
     */
    public function calculateWeeklyWorkingHours(
        User|Freelancer|ServiceProvider $entity,
        Carbon $startDate,
        Carbon $endDate
    ): array {
        $period = CarbonPeriod::create($startDate->copy()->startOfWeek(), '1 week', $endDate->copy()->endOfWeek());
        $individualTimes = $entity->individualTimes()->individualByDateRange($startDate->toDateString(), $endDate->toDateString())->get();

        $individualMinutesPerDay = collect();
        foreach ($individualTimes as $individualTime) {
            foreach ($individualTime->days_of_individual_time as $day) {
                $individualMinutesPerDay[$day] = $individualTime->working_time_minutes ?? 0;
            }
        }

        $weeklyWorkingHours = [];

        foreach ($period as $weekStart) {
            $weekEnd = $weekStart->copy()->endOfWeek();
            $actualStart = $weekStart->greaterThanOrEqualTo($startDate) ? $weekStart : $startDate;
            $actualEnd = $weekEnd->lessThanOrEqualTo($endDate) ? $weekEnd : $endDate;

            $totalPlannedMinutes = 0;
            $totalExpectedMinutes = 0;

            $current = $actualStart->copy();

            while ($current->lte($actualEnd)) {
                $dateStr = $current->toDateString();
                $weekday = strtolower($current->format('l'));

                // TAGESSOLL (Expected)
                if ($entity instanceof User) {
                    $userWorkTime = $entity->workTimes()
                        ->where(function ($q) use ($current) {
                            $q->whereNull('valid_from')->orWhere('valid_from', '<=', $current);
                        })
                        ->where(function ($q) use ($current) {
                            $q->whereNull('valid_until')->orWhere('valid_until', '>=', $current);
                        })
                        ->orderByDesc('valid_from')
                        ->first();

                    $patternTime = $userWorkTime?->{$weekday};
                    if ($patternTime instanceof Carbon) {
                        $dailyTargetMinutes = $patternTime->hour * 60 + $patternTime->minute;
                    } else {
                        $dailyTargetMinutes = round(($entity->weekly_working_hours / 7) * 60);
                    }
                } else {
                    $dailyTargetMinutes = round(($entity->weekly_working_hours / 7) * 60);
                }

                $totalExpectedMinutes += $dailyTargetMinutes;

                // GEPLANT (Planned)
                if ($individualMinutesPerDay->has($dateStr)) {
                    $totalPlannedMinutes += $individualMinutesPerDay[$dateStr];
                } else {
                    if ($entity instanceof User) {
                        $booking = $entity->workTimeBookings()
                            ->where('booking_day', $dateStr)
                            ->first();

                        if ($booking) {
                            $totalPlannedMinutes += $booking->worked_hours;
                        } else {
                            $totalPlannedMinutes += $this->getPlannedShiftMinutesForDay($entity, $current);
                        }
                    } else {
                        $totalPlannedMinutes += $this->getPlannedShiftMinutesForDay($entity, $current);
                    }
                }

                $current->addDay();
            }


            $differenceInMinutes = ($totalPlannedMinutes) - ($totalExpectedMinutes);

            $weeklyWorkingHours[ltrim($weekStart->format('W'), '0')] = [
                'daily_target' => $this->convertMinutesInHours($totalExpectedMinutes, true),
                'planned' => $this->convertMinutesInHours($totalPlannedMinutes, true),
                'difference' => $this->convertMinutesInHours($differenceInMinutes),
                'isMinus' => $differenceInMinutes < 0,
            ];
        }

        return $weeklyWorkingHours;
    }





    private function getPlannedShiftMinutesForDay(User|Freelancer|ServiceProvider $user, Carbon $day): int
    {
        $total = 0;

        $dayStart = $day->copy()->startOfDay();
        $dayEnd = $day->copy()->endOfDay()->addSecond();

        foreach ($user->shifts as $shift) {
            $pivot = $shift->pivot;

            $shiftStart = Carbon::parse($pivot->start_date)->setTimeFrom(Carbon::parse($pivot->start_time));
            $shiftEnd = Carbon::parse($pivot->end_date)->setTimeFrom(Carbon::parse($pivot->end_time));

            // Nur Schichten berücksichtigen, die an diesem Tag aktiv sind
            if ($shiftStart->gt($dayEnd) || $shiftEnd->lt($dayStart)) {
                continue;
            }

            $breakMinutes = (int)($shift->break_minutes ?? 0);

            $workStart = max($shiftStart, $dayStart);
            $workEnd = min($shiftEnd, $dayEnd);

            if ($workStart->lt($workEnd)) {
                $duration = $workStart->diffInMinutes($workEnd) - $breakMinutes;
                $total += max(0, $duration);
            }
        }

        return (int) round($total);
    }


    private function calculateExpectedMinutesBasedOnWorkPattern(User $user, Carbon $startDate, Carbon $endDate): int
    {
        $totalMinutes = 0;
        $current = $startDate->copy();
        $fallbackMinutesPerDay = (int) round(($user->weekly_working_hours / 7) * 60);

        while ($current->lte($endDate)) {
            $weekday = strtolower($current->format('l'));

            $activePattern = $user->workTimes()
                ->where(function ($q) use ($current) {
                    $q->whereNull('valid_from')->orWhere('valid_from', '<=', $current);
                })
                ->where(function ($q) use ($current) {
                    $q->whereNull('valid_until')->orWhere('valid_until', '>=', $current);
                })
                ->orderByDesc('valid_from')
                ->first();

            if ($activePattern && $activePattern->{$weekday}) {
                $time = $activePattern->{$weekday};
                $totalMinutes += $time->hour * 60 + $time->minute;
            } else {
                // Fallback auf durchschnittliche Tagesarbeitszeit
                $totalMinutes += $fallbackMinutesPerDay;
            }

            $current->addDay();
        }

        return $totalMinutes;
    }

}
