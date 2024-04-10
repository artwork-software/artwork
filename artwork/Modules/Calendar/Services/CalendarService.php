<?php

namespace Artwork\Modules\Calendar\Services;

use App\Http\Controllers\CalendarController;
use App\Http\Resources\ProjectCalendarShowEventResource;
use App\Http\Resources\ResourceModels\CalendarEventCollectionResourceModel;
use App\Models\Filter;
use App\Models\Freelancer;
use App\Models\User;
use Artwork\Modules\Project\Models\Project;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class CalendarService
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

        // Aufteilung in Wochen
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
        $shifts = [];
        if ($freelancer) {
            $vacationDays = $freelancer->vacations()->orderBy('date', 'ASC')->get();
            $availabilityDays = $freelancer->availabilities()->orderBy('date', 'ASC')->get();
            $shifts = $freelancer->shifts()->where('is_committed', true)->get();
        }

        if ($user) {
            $vacationDays = $user->vacations()->orderBy('date', 'ASC')->get();
            $availabilityDays = $user->availabilities()->orderBy('date', 'ASC')->get();
            $shifts = $user->shifts()->where('is_committed', true)->get();
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

    public function getCalendarForProjectTab(
        Project $project,
        array $loadedProjectInformation,
        CalendarController $calendar
    ) {
        $showCalendar = $calendar->createCalendarData(type: '', project: $project);
        $eventsAtAGlance = [];

        if (\request('atAGlance') === 'true') {
            $eventsQuery = $project->events();
            $filteredEvents = $calendar->filterEvents($eventsQuery, null, null, null, $project);

            $eventsAtAGlance = ProjectCalendarShowEventResource::collection(
                $filteredEvents
                    ->with(['room','project','creator'])
                    ->orderBy('start_time', 'ASC')
                    ->get()
            )->collection->groupBy('room.id');
        }

        if (\request('startDate') && \request('endDate')) {
            $startDate = Carbon::create(\request('startDate'))->startOfDay();
            $endDate = Carbon::create(\request('endDate'))->endOfDay();
        } else {
            $startDate = Carbon::now()->startOfDay();
            $endDate = Carbon::now()->addWeeks()->endOfDay();
        }

        $loadedProjectInformation['CalendarTab'] = [
            'eventsAtAGlance' => $eventsAtAGlance,
            'calendar' => $showCalendar['roomsWithEvents'],
            'dateValue' => $showCalendar['dateValue'],
            'days' => $showCalendar['days'],
            'selectedDate' => $showCalendar['selectedDate'],
            'rooms' => $calendar->filterRooms($startDate, $endDate)->get(),
            'events' => new CalendarEventCollectionResourceModel(
                areas: $showCalendar['filterOptions']['areas'],
                projects: $showCalendar['filterOptions']['projects'],
                eventTypes: $showCalendar['filterOptions']['eventTypes'],
                roomCategories: $showCalendar['filterOptions']['roomCategories'],
                roomAttributes: $showCalendar['filterOptions']['roomAttributes'],
                events: $calendar->getEventsOfInterval($startDate, $endDate, $project),
                filter: Filter::query()->where('user_id', Auth::id())->get(),
            ),
            'filterOptions' => $showCalendar["filterOptions"],
            'personalFilters' => $showCalendar['personalFilters'],
            'eventsWithoutRoom' => $showCalendar['eventsWithoutRoom'],
            'user_filters' => $showCalendar['user_filters'],
        ];

        return $loadedProjectInformation;
    }
}
