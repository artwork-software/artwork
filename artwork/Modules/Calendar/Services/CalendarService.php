<?php

namespace Artwork\Modules\Calendar\Services;

use App\Models\Freelancer;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

readonly class CalendarService
{
    public function createVacationAndAvailabilityPeriodCalendar($month = null): Collection
    {
        $date = Carbon::today();
        $daysInMonth = $date->daysInMonth;
        $firstDayOfMonth = Carbon::now()->startOfMonth();
        $lastDayOfMonth = Carbon::now()->endOfMonth();
        if ($month) {
            $date = Carbon::parse($month);
            $daysInMonth = $date->daysInMonth;
            $firstDayOfMonth = Carbon::parse($month)->startOfMonth();
            $lastDayOfMonth = Carbon::parse($month)->endOfMonth();
        }

        // Assuming you start the week on Monday
        $paddingStart = $firstDayOfMonth->dayOfWeekIso - 1;
        $paddingEnd = 7 - $lastDayOfMonth->dayOfWeekIso;

        $days = collect()->range(1 - $paddingStart, $daysInMonth + $paddingEnd)
            ->map(function ($day) use ($date) {
                $currentDay = $date->copy()->startOfMonth()->addDays($day - 1);
                return [
                    'date' => $currentDay->format('Y-m-d'),
                    'day' => $currentDay->day,
                    'inMonth' => $currentDay->month === $date->month,
                    'isToday' => $currentDay->isToday(),
                ];
            });

        return $days->chunk(7);
    }

    /**
     * @return array<string, mixed>
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    //@todo: fix phpcs error - fix complexity
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
    public function getAvailabilityData(?Freelancer $freelancer = null, ?User $user = null, $month = null): array
    {
        $vacationDays = [];
        $availabilityDays = [];
        if ($freelancer) {
            $vacationDays = $freelancer->vacations()->orderBy('date', 'ASC')->get();
            $availabilityDays = $freelancer->availabilities()->orderBy('date', 'ASC')->get();
        }

        if ($user) {
            $vacationDays = $user->vacations()->orderBy('date', 'ASC')->get();
            $availabilityDays = $user->availabilities()->orderBy('date', 'ASC')->get();
        }

        $currentMonth = Carbon::now()->startOfMonth();

        if ($month) {
            $currentMonth = Carbon::parse($month)->startOfMonth();
        }

        $startDate = $currentMonth->copy()->startOfWeek();
        $endDate = $currentMonth->copy()->endOfMonth()->endOfWeek();

        $calendarData = [];
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $onVacation = false;
            $hasAvailability = false;
            $hasConflict = false;
            $weekNumber = $currentDate->weekOfYear;
            $day = $currentDate->day;
            foreach ($vacationDays as $vacationDay) {
                if ($currentDate->isSameDay($vacationDay->date)) {
                    $onVacation = true;
                }
            }

            foreach ($availabilityDays as $availabilityDay) {
                if ($currentDate->isSameDay($availabilityDay->date)) {
                    $hasAvailability = true;
                }
            }

            // check if vacation and availability conflicts
            foreach ($vacationDays as $vacationDay) {
                if ($vacationDay->conflicts()->exists() && $currentDate->isSameDay($vacationDay->date)) {
                    $hasConflict = true;
                }
            }

            foreach ($availabilityDays as $availabilityDay) {
                if ($availabilityDay->conflicts()->exists() && $currentDate->isSameDay($availabilityDay->date)) {
                    $hasConflict = true;
                }
            }


            if (!isset($calendarData[$weekNumber])) {
                $calendarData[$weekNumber] = ['weekNumber' => $weekNumber, 'days' => []];
            }

            $notInMonth = !$currentDate->isSameMonth($currentMonth);

            $calendarData[$weekNumber]['days'][] = [
                'day' => $day,
                'notInMonth' => $notInMonth,
                'onVacation' => $onVacation,
                'hasAvailability' => $hasAvailability,
                'hasConflict' => $hasConflict,
                'day_formatted' => $currentDate->format('Y-m-d'),
                'isToday' => $currentDate->isToday(),
            ];

            $currentDate->addDay();
        }

        $dateToShow = [
            $currentMonth->locale(\session()->get('locale') ?? config('app.fallback_locale'))->isoFormat('MMMM YYYY'),
            $currentMonth->copy()->startOfMonth()->toDate()
        ];

        return [
            'calendarData' => array_values($calendarData),
            'dateToShow' => $dateToShow
        ];
    }
}
