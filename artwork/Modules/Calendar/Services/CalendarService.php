<?php

namespace Artwork\Modules\Calendar\Services;

use App\Http\Controllers\FilterController;
use Artwork\Modules\Area\Services\AreaService;
use Artwork\Modules\Availability\Models\Available;
use Artwork\Modules\Calendar\Filter\CalendarFilter;
use Artwork\Modules\Event\Http\Resources\CalendarEventResource;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\EventType\Services\EventTypeService;
use Artwork\Modules\Filter\Services\FilterService;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Services\ProjectService;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Room\Services\RoomService;
use Artwork\Modules\RoomAttribute\Services\RoomAttributeService;
use Artwork\Modules\RoomCategory\Services\RoomCategoryService;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Services\UserService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
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
    public function getAvailabilityData(Available $available, $month = null): array
    {
        $vacationDays = [];
        $availabilityDays = [];
        $vacationDays = $available->vacations()->orderBy('date', 'ASC')->get();
        $availabilityDays = $available->availabilities()->orderBy('date', 'ASC')->get();

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
            array_values($calendarData),
            $dateToShow
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function createCalendarData(
        Carbon $startDate,
        Carbon $endDate,
        UserService $userService,
        FilterService $filterService,
        FilterController $filterController,
        RoomService $roomService,
        RoomCategoryService $roomCategoryService,
        RoomAttributeService $roomAttributeService,
        EventTypeService $eventTypeService,
        AreaService $areaService,
        ProjectService $projectService,
        ?CalendarFilter $calendarFilter,
        ?Room $room = null,
        ?Project $project = null,
    ): array {
        $periodArray = [];
        foreach (($calendarPeriod = CarbonPeriod::create($startDate, $endDate)) as $period) {
            $periodArray[] = [
                'day' => $period->format('d.m.'),
                'day_string' => $period->shortDayName,
                'is_weekend' => $period->isWeekend(),
                'full_day' => $period->format('d.m.Y'),
                'short_day' => $period->format('d.m'),
                'week_number' => $period->weekOfYear,
                'is_monday' => $period->isMonday(),
            ];
        }

        return [
            'days' => $periodArray,
            'dateValue' => [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')],
            // only used for dashboard -> default Dashboard should show Vuecal-Daily calendar with current day
            'calendarType' => $startDate->format('d.m.Y') === $endDate->format('d.m.Y') ?
                'daily' :
                'individual',
            // Selected Date is needed for change from individual Calendar to VueCal-Daily, so that vuecal knows which
            // date to load
            'selectedDate' => $startDate->format('Y-m-d') === $endDate->format('Y-m-d') ?
                $startDate->format('Y-m-d') :
                null,
            'roomsWithEvents' => empty($room) ?
                $roomService->collectEventsForRooms(
                    $roomService->getFilteredRooms(
                        $startDate,
                        $endDate,
                        $calendarFilter
                    ),
                    $calendarPeriod,
                    $project
                ) :
                $roomService->collectEventsForRoom($room, $calendarPeriod, $project, $calendarFilter),
            'eventsWithoutRoom' => empty($room) ?
                CalendarEventResource::collection(Event::hasNoRoom()->get())->resolve() :
                [],
            'filterOptions' => $filterService->getCalendarFilterDefinitions(
                $roomCategoryService,
                $roomAttributeService,
                $eventTypeService,
                $areaService,
                $projectService,
                $roomService
            ),
            'personalFilters' => $filterController->index(),
            'user_filters' => $userService->getAuthUser()->calendar_filter,
        ];
    }

    public function getEventsAtAGlance($startDate, $endDate): Collection
    {
        $initialEventQuery = Event::query();

        $filteredEventsQuery = $this->filterEvents($initialEventQuery, $startDate, $endDate, null, null);

        $eventsByRoom = $filteredEventsQuery
            ->with(['room', 'project', 'creator'])
            ->orderBy('start_time', 'ASC')->get();

        return CalendarEventResource::collection($eventsByRoom)->collection->groupBy('room.id');
    }

    public function getEventsOfInterval($startDate, $endDate, ?Project $project = null): Collection
    {
        $all_events = Event::query();
        $startDate = Carbon::parse($startDate)->startOfDay();
        $endDate = Carbon::parse($endDate)->endOfDay();

        $filteredEvents = $this->filterEvents($all_events, $startDate, $endDate, null, $project);
        return $filteredEvents->get();
    }

    //@todo: fix phpcs error - refactor function because complexity is rising
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
    public function filterEvents(
        $query,
        $startDate,
        $endDate,
        ?Room $room,
        ?Project $project
    ): Builder|HasMany {
        $user = Auth::user();
        $calendarFilter = $user->shift_calendar_filter()->first();

        $isLoud = $calendarFilter->is_loud ?? false;
        $isNotLoud = $calendarFilter->is_not_loud ?? false;
        $hasAudience = $calendarFilter->has_audience ?? false;
        $hasNoAudience = $calendarFilter->has_no_audience ?? false;
        $showAdjoiningRooms = $calendarFilter->show_adjoining_rooms ?? false;
        $eventTypeIds = $calendarFilter->event_types ?? null;
        $roomIds = $calendarFilter->rooms ?? null;
        $areaIds = $calendarFilter->areas ?? null;
        $roomAttributeIds = $calendarFilter->room_attributes ?? null;
        $roomCategoryIds = $calendarFilter->room_categories ?? null;

        return $query
            ->when($project, fn(Builder $builder) => $builder->where('project_id', $project->id ?? null))
            ->when($room, fn(Builder $builder) => $builder->where('room_id', $room->id ?? null))
            ->unless(
                empty($roomIds) && empty($areaIds) && empty($roomAttributeIds) && empty($roomCategoryIds),
                fn(Builder $builder) => $builder->whereHas(
                    'room',
                    fn(Builder $roomBuilder) => $roomBuilder
                        ->when($roomIds, fn(Builder $roomBuilder) => $roomBuilder->whereIn('rooms.id', $roomIds))
                        ->when($areaIds, fn(Builder $roomBuilder) => $roomBuilder->whereIn('area_id', $areaIds))
                        ->when($showAdjoiningRooms, fn(Builder $roomBuilder) => $roomBuilder->with('adjoining_rooms'))
                        ->when(
                            $roomAttributeIds,
                            fn(Builder $roomBuilder) => $roomBuilder
                                ->whereHas(
                                    'attributes',
                                    fn(Builder $roomAttributeBuilder) => $roomAttributeBuilder
                                        ->whereIn('room_attributes.id', $roomAttributeIds)
                                )
                        )
                        ->when(
                            $roomCategoryIds,
                            fn(Builder $roomBuilder) => $roomBuilder
                                ->whereHas(
                                    'categories',
                                    fn(Builder $roomCategoryBuilder) => $roomCategoryBuilder
                                        ->whereIn('room_categories.id', $roomCategoryIds)
                                )
                        )
                        ->without(['admins'])
                )
            )
            ->unless(
                empty($eventTypeIds),
                function ($query) use ($eventTypeIds) {
                    return $query->where(
                        function ($query) use ($eventTypeIds): void {
                            $query->whereIn('event_type_id', $eventTypeIds)
                                ->orWhereHas(
                                    'subEvents',
                                    function ($query) use ($eventTypeIds): void {
                                        $query->whereIn('event_type_id', $eventTypeIds);
                                    }
                                );
                        }
                    );
                }
            )
            ->unless(!$hasAudience, fn(Builder $builder) => $builder->where('audience', true))
            ->unless(!$hasNoAudience, fn(Builder $builder) => $builder->where('audience', false))
            ->unless(!$isLoud, fn(Builder $builder) => $builder->where('is_loud', true))
            ->unless(!$isNotLoud, fn(Builder $builder) => $builder->where('is_loud', false))
            ->when($startDate, fn(Builder $builder) => $builder->startAndEndTimeOverlap($startDate, $endDate));
    }
}
