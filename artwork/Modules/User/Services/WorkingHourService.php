<?php

namespace Artwork\Modules\User\Services;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Repositories\ShiftRepository;
use Artwork\Modules\Shift\Services\ShiftService;
use Artwork\Modules\User\Http\Resources\UserShiftPlanResource;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Repositories\UserRepository;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

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

    public function convertMinutesInHours(int|float $minutes): string
    {
        $hours = intdiv(abs($minutes), 60); // Ganze Stunden
        $remainingMinutes = abs($minutes) % 60; // Verbleibende Minuten

        // Formatierung: Hinzufügen von "h" und "m" und Umgang mit negativen Werten
        $formattedTime = sprintf("%dh %dm", $hours, $remainingMinutes);

        // Wenn die Minuten negativ sind, füge ein Minuszeichen hinzu
        return $minutes < 0 ? '-' . $formattedTime : $formattedTime;
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
                    ($user->weekly_working_hours / 7) * ($startDate->diffInDays($endDate) + 1) * 60
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

        $weeklyWorkingHours = [];

        foreach ($period as $weekStart) {
            $weekEnd = $weekStart->copy()->endOfWeek();

            // Berechne den tatsächlichen Zeitraum für diese Woche
            $actualStart = $weekStart->greaterThanOrEqualTo($startDate) ? $weekStart : $startDate;
            $actualEnd = $weekEnd->lessThanOrEqualTo($endDate) ? $weekEnd : $endDate;

            // Berechne die Anzahl der Tage in der Woche
            $daysInWeek = $actualStart->diffInDays($actualEnd) + 1;

            // Berechne die geplanten Arbeitsstunden in Minuten (basierend auf der Wochenarbeitszeit des Mitarbeiters)
            $dailyWorkingHours = $entity->weekly_working_hours / 7;
            $totalPlannedWorkingHoursInMinutes = ($daysInWeek * $dailyWorkingHours) * 60;

            // Berechne die tatsächliche Schichtzeit in Minuten für diese Woche
            $actualShiftTimeInMinutes = $this->calculateShiftTime($entity, $actualStart, $actualEnd);

            // Berechne die Differenz zwischen den geplanten und tatsächlichen Stunden
            $differenceInMinutes = $actualShiftTimeInMinutes - $totalPlannedWorkingHoursInMinutes;

            // Konvertiere die Minuten in Stunden und Minuten für die Ausgabe
            $weeklyWorkingHours[ltrim($weekStart->format('W'), '0')] = [
                'planned_hours' => $this->convertMinutesInHours($totalPlannedWorkingHoursInMinutes),
                'actual_hours' => $this->convertMinutesInHours($actualShiftTimeInMinutes),
                'difference' => $this->convertMinutesInHours($differenceInMinutes)
            ];
        }

        return $weeklyWorkingHours;
    }
}
