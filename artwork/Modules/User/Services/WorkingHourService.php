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
                if ($bookingsPerDay->has($dateStr)) {
                    // Tag mit Buchung: NUR Buchung
                    $plannedMinutes[$userId] += (int) $bookingsPerDay[$dateStr];
                } else {
                    // Ohne Buchung: Schicht + Individual
                    $plannedMinutes[$userId] += (int) ($shiftMinutesPerDay[$dateStr] ?? 0);

                    if ($individualMinutesPerDay->has($dateStr)) {
                        $plannedMinutes[$userId] += (int) $individualMinutesPerDay[$dateStr];
                    }
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
        $startTimestamp = $startDate->copy()->startOfDay()->timestamp;
        $endTimestamp = $endDate->copy()->endOfDay()->timestamp;

        // Initialisiere alle Tage mit 0
        $current = $startDate->copy()->startOfDay();
        while ($current->lte($endDate)) {
            $shiftMinutesPerDay[$current->toDateString()] = 0;
            $current->addDay();
        }

        foreach ($user->shifts as $shift) {
            $pivot = $shift->pivot;
            // 1) Datum/Zeit aus Pivot ODER aus Shift-Model
            $startDateStr = $pivot->start_date ?? $shift->start_date ?? null;
            $endDateStr   = $pivot->end_date   ?? $shift->end_date   ?? null;

            // Achtung: im Shift-Model heißen sie start/end, im Pivot start_time/end_time
            $startTimeStr = $pivot->start_time ?? $shift->start ?? null;
            $endTimeStr   = $pivot->end_time   ?? $shift->end   ?? null;

            if (!$startDateStr || !$startTimeStr || !$endDateStr || !$endTimeStr) {
                continue;
            }

            $shiftStart = Carbon::parse($startDateStr)->setTimeFromTimeString(Carbon::parse($startTimeStr)->toTimeString());
            $shiftEnd   = Carbon::parse($endDateStr)->setTimeFromTimeString(Carbon::parse($endTimeStr)->toTimeString());


            if ($shiftEnd->timestamp < $startTimestamp || $shiftStart->timestamp > $endTimestamp) {
                continue;
            }

            $breakMinutes = (int)($shift->break_minutes ?? 0);

            $shiftStartDay = $shiftStart->copy()->startOfDay();
            $shiftEndDay = $shiftEnd->copy()->startOfDay();

            $dayStart = max($shiftStartDay, $startDate->copy()->startOfDay());
            $dayEnd = min($shiftEndDay, $endDate->copy()->startOfDay());

            // Iteriere nur über betroffene Tage
            $currentDay = $dayStart->copy();
            $breakAlreadyDeducted = false;
            while ($currentDay->lte($dayEnd)) {
                $dateStr = $currentDay->toDateString();
                $dayStartTimestamp = $currentDay->timestamp;
                $dayEndTimestamp = $currentDay->copy()->endOfDay()->timestamp;

                $workStartTimestamp = max($shiftStart->timestamp, $dayStartTimestamp);
                $workEndTimestamp = min($shiftEnd->timestamp, $dayEndTimestamp);

                if ($workStartTimestamp < $workEndTimestamp) {
                    $duration = (int)(($workEndTimestamp - $workStartTimestamp) / 60);
                    // Bei mehrtägigen Schichten: Pause nur einmal (am ersten Tag) abziehen
                    if (!$breakAlreadyDeducted) {
                        $duration -= $breakMinutes;
                        $breakAlreadyDeducted = true;
                    }
                    $shiftMinutesPerDay[$dateStr] += max(0, $duration);
                }

                $currentDay->addDay();
            }
        }

        return $shiftMinutesPerDay;
    }

    public function calculateShiftTime(
        User|Freelancer|ServiceProvider $entity,
        Carbon $startDate,
        Carbon $endDate
    ): int {
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

        // Precompute shift minutes für alle Tage einmal
        $shiftMinutesPerDay = $this->precomputeShiftMinutesForDays($entity, $startDate, $endDate);

        $bookingsPerDay = collect();
        if ($entity instanceof User) {
            $bookings = $entity->workTimeBookings()
                ->whereBetween('booking_day', [$startDate->toDateString(), $endDate->toDateString()])
                ->get();

            foreach ($bookings as $booking) {
                $bookingDay = $booking->booking_day;
                if ($bookingDay === null || !is_scalar($bookingDay)) {
                    continue;
                }
                if (!$bookingsPerDay->has($bookingDay)) {
                    $bookingsPerDay[$bookingDay] = 0;
                }
                $bookingsPerDay[$bookingDay] += $booking->worked_hours;
            }
        }

        $totalMinutes = 0;
        $current = $startDate->copy();
        while ($current->lte($endDate)) {
            $dateStr = $current->toDateString();

            if ($entity instanceof User && $bookingsPerDay->has($dateStr)) {
                // Vergangenheit/Tag mit Buchung: NUR Buchung
                $totalMinutes += (int) $bookingsPerDay[$dateStr];
            } else {
                // Zukunft/ohne Buchung: Schicht + Individual
                $totalMinutes += (int) ($shiftMinutesPerDay[$dateStr] ?? 0);

                if ($individualMinutesPerDay->has($dateStr)) {
                    $totalMinutes += (int) $individualMinutesPerDay[$dateStr];
                }
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
        ?User $currentUser = null
    ): array {
        // Im Konstruktor kann das zu circluar dependency führen, deswegen über den Container
        $workerShiftPlanService = app(\Artwork\Modules\Worker\Services\WorkerShiftPlanService::class);
        $workerService = app(\Artwork\Modules\Worker\Services\WorkerService::class);

        $workers = $this->userRepository->getWorkers($startDate, $endDate);
        $workers = $workerShiftPlanService->loadWorkerRelations($workers, $startDate, $endDate);
        $workers = $workerShiftPlanService->filterByQualifications($workers, $currentUser);
        $qualificationsCache = $workerService->buildQualificationsCache($workers);

        $usersWithPlannedWorkingHours = [];

        /** @var User $user */
        foreach ($workers as $user) {
            /** @var JsonResource $desiredResourceClass */
            $desiredUserResource = $desiredResourceClass::make($user);

            $additionalData = [
                'workTimeBalance' => $this->convertMinutesInHours($user->work_time_balance ?? 0),
            ];

            $userData = $workerShiftPlanService->buildWorkerData(
                $user,
                $desiredUserResource,
                $qualificationsCache,
                $startDate,
                $endDate,
                $addVacationsAndAvailabilities,
                $additionalData
            );

            if ($addVacationsAndAvailabilities) {
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
     * Precompute workTime patterns für einen User
     *
     * @param User $user
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array<string, array{pattern: \Artwork\Modules\User\Models\UserWorkTime|null, weekday: string}>
     */
    private function precomputeWorkTimePatterns(User $user, Carbon $startDate, Carbon $endDate): array
    {
        $patternMap = [];

        // Sortiere workTimes nach valid_from (neueste zuerst)
        $workTimes = $user->workTimes->sortByDesc(function ($workTime) {
            return $workTime->valid_from ? Carbon::parse($workTime->valid_from)->timestamp : 0;
        });

        // Parse valid_from/valid_until einmal für alle workTimes
        $parsedWorkTimes = [];
        foreach ($workTimes as $workTime) {
            $parsedWorkTimes[] = [
                'valid_from' => $workTime->valid_from ? Carbon::parse($workTime->valid_from)->startOfDay()->timestamp : null,
                'valid_until' => $workTime->valid_until ? Carbon::parse($workTime->valid_until)->endOfDay()->timestamp : null,
                'workTime' => $workTime,
            ];
        }

        // Iteriere über alle Tage und finde aktives Pattern
        $current = $startDate->copy()->startOfDay();
        while ($current->lte($endDate)) {
            $dateStr = $current->toDateString();
            $dateTimestamp = $current->timestamp;
            $weekday = strtolower($current->format('l'));

            // Finde aktives Pattern (neuestes zuerst)
            $activePattern = null;
            foreach ($parsedWorkTimes as $parsed) {
                if (
                    (!$parsed['valid_from'] || $parsed['valid_from'] <= $dateTimestamp) &&
                    (!$parsed['valid_until'] || $parsed['valid_until'] >= $dateTimestamp)
                ) {
                    $activePattern = $parsed['workTime'];
                    break; // Neuestes Pattern gefunden
                }
            }

            $patternMap[$dateStr] = [
                'pattern' => $activePattern,
                'weekday' => $weekday,
            ];

            $current->addDay();
        }

        return $patternMap;
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

        // Precompute workTime patterns for all User instances
        $allWorkTimePatterns = [];
        foreach ($users as $user) {
            if ($user instanceof User) {
                $allWorkTimePatterns[$user->id] = $this->precomputeWorkTimePatterns($user, $startDate, $endDate);
            }
        }

        // Process each user
        foreach ($users as $user) {
            $userId = $user->id;
            $weeklyWorkingHoursCache[$userId] = [];

            // Create a map of vacations per day to identify OFF_WORK days
            $offWorkDays = collect();
            foreach ($user->vacations as $vacation) {
                if ($vacation->type === 'OFF_WORK' || $vacation->comment === 'OFF_WORK') {
                    $offWorkDays[$vacation->date instanceof Carbon ? $vacation->date->toDateString() : Carbon::parse($vacation->date)->toDateString()] = true;
                }
            }

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

            // Get workTime patterns for this user (if User instance)
            $workTimePatterns = $allWorkTimePatterns[$userId] ?? null;

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

                    // Calculate expected minutes (TAGESSOLL)
                    if ($offWorkDays->has($dateStr)) {
                        $dailyTargetMinutes = 0;
                    } elseif ($user instanceof User && $workTimePatterns) {
                        $patternData = $workTimePatterns[$dateStr] ?? null;
                        $activePattern = $patternData['pattern'] ?? null;
                        $weekday = $patternData['weekday'] ?? strtolower($current->format('l'));

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

                    // Immer Schichtminuten dazurechnen (falls vorhanden)
                    $totalPlannedMinutes += $shiftMinutesPerDay[$dateStr] ?? 0;

                    if ($bookingsPerDay->has($dateStr)) {
                        $totalPlannedMinutes += $bookingsPerDay[$dateStr];
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

        // Lade individualTimes einmal
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

        // Precompute shift minutes für alle Tage einmal
        $shiftMinutesPerDay = $this->precomputeShiftMinutesForDays($entity, $startDate, $endDate);

        // Precompute workTime patterns für User
        $workTimePatterns = null;
        if ($entity instanceof User) {
            $workTimePatterns = $this->precomputeWorkTimePatterns($entity, $startDate, $endDate);
        }

        // Create a map of vacations per day to identify OFF_WORK days
        $offWorkDays = collect();
        foreach ($entity->vacations as $vacation) {
            if ($vacation->type === 'OFF_WORK' || $vacation->comment === 'OFF_WORK') {
                $offWorkDays[$vacation->date instanceof Carbon ? $vacation->date->toDateString() : Carbon::parse($vacation->date)->toDateString()] = true;
            }
        }

        // Lade bookings für User einmal
        $bookingsPerDay = collect();
        if ($entity instanceof User) {
            $bookings = $entity->workTimeBookings()
                ->whereBetween('booking_day', [$startDate->toDateString(), $endDate->toDateString()])
                ->get();

            foreach ($bookings as $booking) {
                $bookingDay = $booking->booking_day;
                if ($bookingDay === null || !is_scalar($bookingDay)) {
                    continue;
                }
                if (!$bookingsPerDay->has($bookingDay)) {
                    $bookingsPerDay[$bookingDay] = 0;
                }
                $bookingsPerDay[$bookingDay] += $booking->worked_hours;
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

                // TAGESSOLL (Expected)
                if ($offWorkDays->has($dateStr)) {
                    $dailyTargetMinutes = 0;
                } elseif ($entity instanceof User && $workTimePatterns) {
                    $patternData = $workTimePatterns[$dateStr] ?? null;
                    $activePattern = $patternData['pattern'] ?? null;
                    $weekday = $patternData['weekday'] ?? strtolower($current->format('l'));

                    $patternTime = $activePattern?->{$weekday};
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
                }

                // Immer Schichtzeit dazu
                $totalPlannedMinutes += $shiftMinutesPerDay[$dateStr] ?? 0;

                if ($entity instanceof User && $bookingsPerDay->has($dateStr)) {
                    $totalPlannedMinutes += $bookingsPerDay[$dateStr];
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

        $dayStartTimestamp = $day->copy()->startOfDay()->timestamp;
        $dayEndTimestamp = $day->copy()->endOfDay()->timestamp;

        foreach ($user->shifts as $shift) {
            $pivot = $shift->pivot;

            // Skip wenn Pivot-Daten fehlen
            if (!$pivot || !$pivot->start_date || !$pivot->start_time || !$pivot->end_date || !$pivot->end_time) {
                continue;
            }

            // Parse Shift-Zeiten einmal
            $shiftStart = Carbon::parse($pivot->start_date)->setTimeFromTimeString(Carbon::parse($pivot->start_time)->toTimeString());
            $shiftEnd = Carbon::parse($pivot->end_date)->setTimeFromTimeString(Carbon::parse($pivot->end_time)->toTimeString());

            // Prüfe ob Shift diesen Tag überlappt
            if ($shiftEnd->timestamp < $dayStartTimestamp || $shiftStart->timestamp > $dayEndTimestamp) {
                continue;
            }

            $breakMinutes = (int)($shift->break_minutes ?? 0);

            // Berechne Überlappung mit Timestamps
            $workStartTimestamp = max($shiftStart->timestamp, $dayStartTimestamp);
            $workEndTimestamp = min($shiftEnd->timestamp, $dayEndTimestamp);

            if ($workStartTimestamp < $workEndTimestamp) {
                $duration = (int)(($workEndTimestamp - $workStartTimestamp) / 60);
                // Bei mehrtägigen Schichten: Pause nur am ersten Tag der Schicht abziehen
                $shiftStartDay = $shiftStart->copy()->startOfDay();
                $isFirstDayOfShift = $day->copy()->startOfDay()->equalTo($shiftStartDay);
                if ($isFirstDayOfShift) {
                    $duration -= $breakMinutes;
                }
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

            // Create a map of vacations per day to identify OFF_WORK days
            $offWorkDays = collect();
            foreach ($user->vacations as $vacation) {
                if ($vacation->type === 'OFF_WORK' || $vacation->comment === 'OFF_WORK') {
                    $offWorkDays[$vacation->date instanceof Carbon ? $vacation->date->toDateString() : Carbon::parse($vacation->date)->toDateString()] = true;
                }
            }

            // Get all work times for this user (already eager loaded)
            $workTimes = $user->workTimes;

            foreach ($dateArray as $dateStr) {
                if ($offWorkDays->has($dateStr)) {
                    continue; // Expected minutes for OFF_WORK day is 0
                }

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

        // Create a map of vacations per day to identify OFF_WORK days
        $offWorkDays = collect();
        foreach ($user->vacations as $vacation) {
            if ($vacation->type === 'OFF_WORK' || $vacation->comment === 'OFF_WORK') {
                $offWorkDays[$vacation->date instanceof Carbon ? $vacation->date->toDateString() : Carbon::parse($vacation->date)->toDateString()] = true;
            }
        }

        while ($current->lte($endDate)) {
            $dateStr = $current->toDateString();
            if ($offWorkDays->has($dateStr)) {
                $current->addDay();
                continue;
            }
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
