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

    /**
     * Precompute planned minutes for all users at once
     *
     * @param Collection $users Collection of users
     * @param Carbon $startDate Start date
     * @param Carbon $endDate End date
     * @return array Array of planned minutes indexed by user ID
     */
    private function precomputePlannedMinutes(Collection $users, Carbon $startDate, Carbon $endDate): array
    {
        $plannedMinutes = [];
        $dateRange = CarbonPeriod::create($startDate, $endDate);
        $dateArray = [];

        // Create an array of all dates in the range
        foreach ($dateRange as $date) {
            $dateArray[] = $date->toDateString();
        }

        // Process each user
        foreach ($users as $user) {
            $userId = $user->id;
            $plannedMinutes[$userId] = 0;

            // Create a map of individual minutes per day
            $individualMinutesPerDay = collect();
            foreach ($user->individualTimes as $individualTime) {
                foreach ($individualTime->days_of_individual_time as $day) {
                    // Skip if day is null or not a valid array key type
                    if ($day === null || !is_scalar($day)) {
                        continue;
                    }
                    $individualMinutesPerDay[$day] = $individualTime->working_time_minutes ?? 0;
                }
            }

            // Create a map of bookings per day
            $bookingsPerDay = collect();
            foreach ($user->workTimeBookings as $booking) {
                $bookingDay = $booking->booking_day;
                // Skip if booking_day is null or not a valid array key type
                if ($bookingDay === null || !is_scalar($bookingDay)) {
                    continue;
                }

                if (!$bookingsPerDay->has($bookingDay)) {
                    $bookingsPerDay[$bookingDay] = 0;
                }
                $bookingsPerDay[$bookingDay] += $booking->worked_hours;
            }

            // Precompute shift minutes for all days
            $shiftMinutesPerDay = $this->precomputeShiftMinutesForDays($user, $startDate, $endDate);

            // Calculate total minutes for each day
            foreach ($dateArray as $dateStr) {
                // Add individual time if available
                if ($individualMinutesPerDay->has($dateStr)) {
                    $plannedMinutes[$userId] += $individualMinutesPerDay[$dateStr];
                }

                // Add either booking or shift time
                if ($bookingsPerDay->has($dateStr)) {
                    $plannedMinutes[$userId] += $bookingsPerDay[$dateStr];
                } else {
                    $plannedMinutes[$userId] += $shiftMinutesPerDay[$dateStr] ?? 0;
                }
            }

            $plannedMinutes[$userId] = max(0, $plannedMinutes[$userId]);
        }

        return $plannedMinutes;
    }

    /**
     * Precompute shift minutes for all days for a user
     *
     * @param User|Freelancer|ServiceProvider $user User
     * @param Carbon $startDate Start date
     * @param Carbon $endDate End date
     * @return array Array of shift minutes indexed by date string
     */
    private function precomputeShiftMinutesForDays(User|Freelancer|ServiceProvider $user, Carbon $startDate, Carbon $endDate): array
    {
        $shiftMinutesPerDay = [];
        $dateRange = CarbonPeriod::create($startDate, $endDate);

        foreach ($dateRange as $day) {
            $dateStr = $day->toDateString();
            $shiftMinutesPerDay[$dateStr] = 0;

            $dayStart = $day->copy()->startOfDay();
            $dayEnd = $day->copy()->endOfDay();

            foreach ($user->shifts as $shift) {
                $pivot = $shift->pivot;

                $shiftStart = Carbon::parse($pivot->start_date)->setTimeFrom(Carbon::parse($pivot->start_time));
                $shiftEnd = Carbon::parse($pivot->end_date)->setTimeFrom(Carbon::parse($pivot->end_time));

                if ($shiftStart->gt($dayEnd) || $shiftEnd->lt($dayStart)) {
                    continue;
                }

                $breakMinutes = (int)($shift->break_minutes ?? 0);

                $workStart = max($shiftStart, $dayStart);
                $workEnd = min($shiftEnd, $dayEnd);

                if ($workStart->lt($workEnd)) {
                    $duration = $workStart->diffInMinutes($workEnd) - $breakMinutes;
                    $shiftMinutesPerDay[$dateStr] += max(0, $duration);
                }
            }
        }

        return $shiftMinutesPerDay;
    }

    public function calculateShiftTime(
        User|Freelancer|ServiceProvider $entity,
        Carbon $startDate,
        Carbon $endDate
    ): int {
        $totalMinutes = 0;

        // Individuelle Zeiten sammeln
        $individualTimes = $entity->individualTimes()
            ->individualByDateRange($startDate->toDateString(), $endDate->toDateString())
            ->get();

        $individualMinutesPerDay = collect();
        foreach ($individualTimes as $individualTime) {
            foreach ($individualTime->days_of_individual_time as $day) {
                // Skip if day is null or not a valid array key type
                if ($day === null || !is_scalar($day)) {
                    continue;
                }
                $individualMinutesPerDay[$day] = $individualTime->working_time_minutes ?? 0;
            }
        }

        $current = $startDate->copy();
        while ($current->lte($endDate)) {
            $dateStr = $current->toDateString();

            // Immer individuelle Zeit draufrechnen (wenn vorhanden)
            if ($individualMinutesPerDay->has($dateStr)) {
                $totalMinutes += $individualMinutesPerDay[$dateStr];
            }

            // Dann entweder Booking oder Schichtzeit draufrechnen
            if ($entity instanceof User) {
                $bookings = $entity->workTimeBookings()
                    ->where('booking_day', $dateStr)
                    ->get();

                if ($bookings->isNotEmpty()) {
                    $totalMinutes += $bookings->sum('worked_hours');
                } else {
                    $totalMinutes += $this->getPlannedShiftMinutesForDay($entity, $current);
                }
            } else {
                $totalMinutes += $this->getPlannedShiftMinutesForDay($entity, $current);
            }

            $current->addDay();
        }

        return max(0, $totalMinutes);
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

        // Eager load all necessary relationships for all workers at once
        $workers = $this->userRepository->getWorkers()->load([
            'shifts',
            'individualTimes' => function ($query) use ($startDate, $endDate): void {
                $query->individualByDateRange($startDate->toDateString(), $endDate->toDateString());
            },
            'workTimeBookings' => function ($query) use ($startDate, $endDate): void {
                $query->whereBetween('booking_day', [$startDate->toDateString(), $endDate->toDateString()]);
            },
            'workTimes' => function ($query) use ($startDate, $endDate): void {
                $query->where(function ($q) use ($endDate): void {
                    $q->whereNull('valid_from')->orWhere('valid_from', '<=', $endDate);
                })->where(function ($q) use ($startDate): void {
                    $q->whereNull('valid_until')->orWhere('valid_until', '>=', $startDate);
                });
            },
            'dayServices',
        ]);

        // Precompute expected minutes for all users
        $expectedMinutesCache = []; //$this->precomputeExpectedMinutes($workers, $startDate, $endDate);

        // Precompute planned minutes for all users
        $plannedMinutesCache = []; //$this->precomputePlannedMinutes($workers, $startDate, $endDate);

        // Precompute weekly working hours for all users
        $weeklyWorkingHoursCache = $this->precomputeWeeklyWorkingHours($workers, $startDate, $endDate);

        /** @var User $user */
        foreach ($workers as $user) {
            /** @var JsonResource $desiredResourceClass */
            $desiredUserResource = $desiredResourceClass::make($user);
            if ($desiredUserResource instanceof UserShiftPlanResource) {
                $desiredUserResource->setStartDate($startDate)->setEndDate($endDate);
            }

            $userId = $user->id;
            $plannedWorkingHours = $this->convertMinutesInHours(
                $plannedMinutesCache[$userId] ?? 0
            );

            $userData = [
                'user' => $desiredUserResource->resolve(),
                'plannedWorkingHours' => $plannedWorkingHours,
                'expectedWorkingHours' => $this->convertMinutesInHours(
                    $expectedMinutesCache[$userId] ?? 0
                ),
                'dayServices' => $user->dayServices?->groupBy('pivot.date'),
                'is_freelancer' => $user->getAttribute('is_freelancer'),
                'individual_times' => $user->individualTimes,
                'shift_comments' => $user->getShiftPlanCommentsForPeriod($startDate, $endDate),
                'workTimeBalance' => $this->convertMinutesInHours($user->work_time_balance ?? 0),
            ];

            $userData['weeklyWorkingHours'] = $weeklyWorkingHoursCache[$userId] ?? [];
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
     * Precompute weekly working hours for all users at once
     *
     * @param Collection $users Collection of users
     * @param Carbon $startDate Start date
     * @param Carbon $endDate End date
     * @return array Array of weekly working hours indexed by user ID
     */
    private function precomputeWeeklyWorkingHours(Collection $users, Carbon $startDate, Carbon $endDate): array
    {
        $weeklyWorkingHoursCache = [];
        $period = CarbonPeriod::create($startDate->copy()->startOfWeek(), '1 week', $endDate->copy()->endOfWeek());
        $weekPeriods = [];

        // Create an array of week periods
        foreach ($period as $weekStart) {
            $weekEnd = $weekStart->copy()->endOfWeek();
            $actualStart = $weekStart->greaterThanOrEqualTo($startDate) ? $weekStart : $startDate;
            $actualEnd = $weekEnd->lessThanOrEqualTo($endDate) ? $weekEnd : $endDate;

            $weekPeriods[] = [
                'weekStart' => $weekStart,
                'actualStart' => $actualStart,
                'actualEnd' => $actualEnd,
                'weekNumber' => ltrim($weekStart->format('W'), '0')
            ];
        }

        // Precompute shift minutes for all days for all users
        $allShiftMinutes = [];
        foreach ($users as $user) {
            $allShiftMinutes[$user->id] = $this->precomputeShiftMinutesForDays($user, $startDate, $endDate);
        }

        // Process each user
        foreach ($users as $user) {
            $userId = $user->id;
            $weeklyWorkingHoursCache[$userId] = [];

            // Create a map of individual minutes per day
            $individualMinutesPerDay = collect();
            foreach ($user->individualTimes as $individualTime) {
                foreach ($individualTime->days_of_individual_time as $day) {
                    // Skip if day is null or not a valid array key type
                    if ($day === null || !is_scalar($day)) {
                        continue;
                    }
                    $individualMinutesPerDay[$day] = $individualTime->working_time_minutes ?? 0;
                }
            }

            // Create a map of bookings per day
            $bookingsPerDay = collect();
            foreach ($user->workTimeBookings as $booking) {
                $bookingDay = $booking->booking_day;
                // Skip if booking_day is null or not a valid array key type
                if ($bookingDay === null || !is_scalar($bookingDay)) {
                    continue;
                }

                if (!$bookingsPerDay->has($bookingDay)) {
                    $bookingsPerDay[$bookingDay] = 0;
                }
                $bookingsPerDay[$bookingDay] += $booking->worked_hours;
            }

            // Get shift minutes for this user
            $shiftMinutesPerDay = $allShiftMinutes[$userId];

            // Process each week
            foreach ($weekPeriods as $weekPeriod) {
                $weekStart = $weekPeriod['weekStart'];
                $actualStart = $weekPeriod['actualStart'];
                $actualEnd = $weekPeriod['actualEnd'];
                $weekNumber = $weekPeriod['weekNumber'];

                $totalPlannedMinutes = 0;
                $totalExpectedMinutes = 0;

                $current = $actualStart->copy();

                // Process each day in the week
                while ($current->lte($actualEnd)) {
                    $dateStr = $current->toDateString();
                    $weekday = strtolower($current->format('l'));

                    // Calculate expected minutes (TAGESSOLL)
                    if ($user instanceof User) {
                        // Find the active pattern for this date
                        $activePattern = null;
                        foreach ($user->workTimes as $workTime) {
                            $validFrom = $workTime->valid_from ? Carbon::parse($workTime->valid_from) : null;
                            $validUntil = $workTime->valid_until ? Carbon::parse($workTime->valid_until) : null;

                            if (
                                (!$validFrom || $validFrom->lte($current)) &&
                                (!$validUntil || $validUntil->gte($current))
                            ) {
                                if (
                                    !$activePattern ||
                                    (!$activePattern->valid_from && $validFrom) ||
                                    ($activePattern->valid_from && $validFrom && $validFrom->gt($activePattern->valid_from))
                                ) {
                                    $activePattern = $workTime;
                                }
                            }
                        }

                        $patternTime = $activePattern?->{$weekday};
                        if ($patternTime instanceof Carbon) {
                            $dailyTargetMinutes = $patternTime->hour * 60 + $patternTime->minute;
                        } else {
                            $dailyTargetMinutes = round(($user->weekly_working_hours / 7) * 60);
                        }
                    } else {
                        $dailyTargetMinutes = round(($user->weekly_working_hours / 7) * 60);
                    }

                    $totalExpectedMinutes += $dailyTargetMinutes;

                    // Calculate planned minutes (GEPLANT)
                    if ($individualMinutesPerDay->has($dateStr)) {
                        $totalPlannedMinutes += $individualMinutesPerDay[$dateStr];
                    }

                    if ($bookingsPerDay->has($dateStr)) {
                        $totalPlannedMinutes += $bookingsPerDay[$dateStr];
                    } else {
                        $totalPlannedMinutes += $shiftMinutesPerDay[$dateStr] ?? 0;
                    }

                    $current->addDay();
                }

                $differenceInMinutes = ($totalPlannedMinutes) - ($totalExpectedMinutes);

                $weeklyWorkingHoursCache[$userId][$weekNumber] = [
                    'daily_target' => $this->convertMinutesInHours($totalExpectedMinutes, true),
                    'planned' => $this->convertMinutesInHours($totalPlannedMinutes, true),
                    'difference' => $this->convertMinutesInHours($differenceInMinutes),
                    'isMinus' => $differenceInMinutes < 0,
                ];
            }
        }

        return $weeklyWorkingHoursCache;
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
                // Skip if day is null or not a valid array key type
                if ($day === null || !is_scalar($day)) {
                    continue;
                }
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
                        ->where(function ($q) use ($current): void {
                            $q->whereNull('valid_from')->orWhere('valid_from', '<=', $current);
                        })
                        ->where(function ($q) use ($current): void {
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
                        $bookings = $entity->workTimeBookings()
                            ->where('booking_day', $dateStr)
                            ->get();

                        if ($bookings->isNotEmpty()) {
                            $totalPlannedMinutes += $bookings->sum('worked_hours');
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
        $dayEnd = $day->copy()->endOfDay();

        foreach ($user->shifts as $shift) {
            $pivot = $shift->pivot;

            $shiftStart = Carbon::parse($pivot->start_date)->setTimeFrom(Carbon::parse($pivot->start_time));
            $shiftEnd = Carbon::parse($pivot->end_date)->setTimeFrom(Carbon::parse($pivot->end_time));

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


    /**
     * Precompute expected minutes for all users at once
     *
     * @param Collection $users Collection of users
     * @param Carbon $startDate Start date
     * @param Carbon $endDate End date
     * @return array Array of expected minutes indexed by user ID
     */
    private function precomputeExpectedMinutes(Collection $users, Carbon $startDate, Carbon $endDate): array
    {
        $expectedMinutes = [];
        $dateRange = CarbonPeriod::create($startDate, $endDate);
        $dateArray = [];
        $weekdayMap = [];

        // Create a map of dates and their weekdays
        foreach ($dateRange as $date) {
            $dateStr = $date->toDateString();
            $weekday = strtolower($date->format('l'));
            $dateArray[] = $dateStr;
            $weekdayMap[$dateStr] = $weekday;
        }

        foreach ($users as $user) {
            $userId = $user->id;
            $expectedMinutes[$userId] = 0;
            $fallbackMinutesPerDay = (int) round(($user->weekly_working_hours / 7) * 60);

            // Get all work times for this user (already eager loaded)
            $workTimes = $user->workTimes;

            foreach ($dateArray as $dateStr) {
                $date = Carbon::parse($dateStr);
                $weekday = $weekdayMap[$dateStr];

                // Find the active pattern for this date
                $activePattern = null;
                foreach ($workTimes as $workTime) {
                    $validFrom = $workTime->valid_from ? Carbon::parse($workTime->valid_from) : null;
                    $validUntil = $workTime->valid_until ? Carbon::parse($workTime->valid_until) : null;

                    if (
                        (!$validFrom || $validFrom->lte($date)) &&
                        (!$validUntil || $validUntil->gte($date))
                    ) {
                        if (
                            !$activePattern ||
                            (!$activePattern->valid_from && $validFrom) ||
                            ($activePattern->valid_from && $validFrom && $validFrom->gt($activePattern->valid_from))
                        ) {
                            $activePattern = $workTime;
                        }
                    }
                }

                if ($activePattern && $activePattern->{$weekday}) {
                    $time = $activePattern->{$weekday};
                    $expectedMinutes[$userId] += $time->hour * 60 + $time->minute;
                } else {
                    $expectedMinutes[$userId] += $fallbackMinutesPerDay;
                }
            }
        }

        return $expectedMinutes;
    }

    private function calculateExpectedMinutesBasedOnWorkPattern(User $user, Carbon $startDate, Carbon $endDate): int
    {
        $totalMinutes = 0;
        $current = $startDate->copy();
        $fallbackMinutesPerDay = (int) round(($user->weekly_working_hours / 7) * 60);

        while ($current->lte($endDate)) {
            $weekday = strtolower($current->format('l'));

            $activePattern = $user->workTimes()
                ->where(function ($q) use ($current): void {
                    $q->whereNull('valid_from')->orWhere('valid_from', '<=', $current);
                })
                ->where(function ($q) use ($current): void {
                    $q->whereNull('valid_until')->orWhere('valid_until', '>=', $current);
                })
                ->orderByDesc('valid_from')
                ->first();

            if ($activePattern && $activePattern->{$weekday}) {
                $time = $activePattern->{$weekday};
                $totalMinutes += $time->hour * 60 + $time->minute;
            } else {
                $totalMinutes += $fallbackMinutesPerDay;
            }

            $current->addDay();
        }

        return $totalMinutes;
    }
}
