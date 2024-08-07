<?php

namespace Artwork\Modules\Calendar\Services;

use App\Http\Controllers\FilterController;
use Artwork\Modules\Area\Services\AreaService;
use Artwork\Modules\Availability\Models\Available;
use Artwork\Modules\Calendar\Filter\CalendarFilter;
use Artwork\Modules\Event\Http\Resources\CalendarEventResource;
use Artwork\Modules\Event\Http\Resources\MinimalCalendarEventResource;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Services\EventService;
use Artwork\Modules\EventType\Services\EventTypeService;
use Artwork\Modules\Filter\Services\FilterService;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Room\Services\RoomService;
use Artwork\Modules\RoomAttribute\Services\RoomAttributeService;
use Artwork\Modules\RoomCategory\Services\RoomCategoryService;
use Artwork\Modules\User\Services\UserService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Throwable;

class CalendarService
{
    public function __construct(private readonly EventService $eventService)
    {
    }

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
     * @throws Throwable
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
        ?Project $project,
        ?CalendarFilter $calendarFilter,
        ?Room $room = null,
        ?bool $desiresInventorySchedulingResource = false
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
                'month_number' => $period->month,
                'is_first_day_of_month' => $period->isSameDay($period->copy()->startOfMonth())
            ];
        }

        $result = [
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
                    roomsWithEvents: $roomService->getFilteredRooms(
                        $startDate,
                        $endDate,
                        $calendarFilter
                    ),
                    calendarPeriod: $calendarPeriod,
                    calendarFilter: $calendarFilter,
                    project: $project,
                    desiresInventorySchedulingResource: $desiresInventorySchedulingResource
                ) :
                $roomService->collectEventsForRoom(
                    room: $room,
                    calendarPeriod: $calendarPeriod,
                    calendarFilter: $calendarFilter,
                    project: $project
                ),
            'eventsWithoutRoom' => empty($room) ?
                CalendarEventResource::collection(
                    $this->eventService->getEventsWithoutRoom(
                        $project,
                        [
                            'room',
                            'creator',
                            'project',
                            'project.managerUsers',
                            'project.state',
                            'shifts',
                            'shifts.craft',
                            'shifts.users',
                            'shifts.freelancer',
                            'shifts.serviceProvider',
                            'shifts.shiftsQualifications',
                            'subEvents.event',
                            'subEvents.event.room'
                        ]
                    )
                )->resolve() :
                [],
            'filterOptions' => $filterService->getCalendarFilterDefinitions(
                $roomCategoryService,
                $roomAttributeService,
                $eventTypeService,
                $areaService,
                $roomService
            ),
            'personalFilters' => $filterController->index(),
            'user_filters' => $userService->getAuthUser()->calendar_filter,
        ];

        return $result;
    }

    /**
     * @return array<int, MinimalCalendarEventResource>
     */
    public function getEventsAtAGlance($startDate, $endDate, ?Project $project = null): array
    {
        $calendarPeriod = CarbonPeriod::create($startDate, $endDate);
        $actualEvents = $eventsForRoom = [];

        $eventsQuery = $this->filterEvents(Event::query(), $startDate, $endDate, null, $project)
            ->with(
                [
                    'room',
                    'creator',
                    'project',
                    'project.managerUsers',
                    'project.state',
                    'shifts',
                    'shifts.craft',
                    'shifts.users',
                    'shifts.freelancer',
                    'shifts.serviceProvider',
                    'shifts.shiftsQualifications',
                    'subEvents.event',
                    'subEvents.event.room'
                ]
            )->orderBy('start_time');

        foreach ($eventsQuery->get()->all() as $event) {
            $eventStart = $event->start_time->isBefore($calendarPeriod->start) ?
                $calendarPeriod->start :
                $event->start_time;
            $eventEnd = $event->end_time->isAfter($calendarPeriod->end) ? $calendarPeriod->end : $event->end_time;
            $eventPeriod = CarbonPeriod::create($eventStart->startOfDay(), $eventEnd->endOfDay());

            foreach ($eventPeriod as $date) {
                $dateKey = $date->format('d.m.Y');
                $actualEvents[$dateKey][] = $event;
            }
        }

        foreach ($actualEvents as $key => $value) {
            $eventsForRoom[$key] = [
                //immediately resolve resource to free used memory
                'events' => MinimalCalendarEventResource::collection($value)->resolve()
            ];
        }

        return $eventsForRoom;
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
