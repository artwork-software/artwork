<?php

namespace Artwork\Modules\Calendar\Services;

use Artwork\Modules\Calendar\DTO\CalendarHolidayDTO;
use Artwork\Modules\Calendar\DTO\CalendarPeriodDTO;
use Artwork\Modules\Calendar\DTO\RoomDTO;
use Artwork\Modules\Event\Http\Resources\CalendarEventResource;
use Artwork\Modules\Event\Services\EventCollectionService;
use Artwork\Modules\Filter\Services\FilterService;
use Artwork\Modules\Holidays\Models\Holiday;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Services\ProjectService;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Room\Repositories\RoomRepository;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Services\UserService;
use Artwork\Modules\UserCalendarFilter\Models\UserCalendarFilter;
use Artwork\Modules\UserCalendarSettings\Models\UserCalendarSettings;
use Artwork\Modules\UserShiftCalendarFilter\Models\UserShiftCalendarFilter;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection as SupportCollection;

readonly class CalendarDataService
{
    public function __construct(
        private RoomRepository $roomRepository,
        private EventCollectionService $eventCollectionService,
        private FilterService $filterService,
        private UserService $userService,
        private ProjectService $projectService,
    ) {
    }

    /**
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param UserShiftCalendarFilter|UserCalendarFilter|null $calendarFilter
     * @param Project|null $project
     * @param Room|null $room
     * @param bool|null $desiresInventorySchedulingResource
     * @return array<string, mixed>
     */
    //@todo: fix phpcs error - refactor function because complexity exceeds allowed maximum
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
    public function createCalendarData(
        Carbon $startDate,
        Carbon $endDate,
        UserShiftCalendarFilter|UserCalendarFilter|null $calendarFilter,
        ?Project $project = null,
        ?Room $room = null,
        ?bool $desiresInventorySchedulingResource = null
    ): array {
        $periodArray = [];
        $user = $this->userService->getAuthUser();
        foreach (($calendarPeriod = CarbonPeriod::create($startDate, $endDate)) as $period) {
            $holidays = Holiday::where(function ($query) use ($period): void {
                $query->where(function ($q) use ($period): void {
                    $q->whereDate('date', '<=', $period->format('Y-m-d'))
                        ->whereDate('end_date', '>=', $period->format('Y-m-d'));
                })->orWhere(function ($q) use ($period): void {
                    $q->where('yearly', true)
                        ->whereMonth('date', $period->month)
                        ->whereDay('end_date', $period->day);
                });
            })->with('subdivisions')->get();
            $periodArray[] = [
                'day' => $period->format('d.m.'),
                'day_string' => $period->shortDayName,
                'is_weekend' => $period->isWeekend(),
                'full_day' => $period->format('d.m.Y'),
                'without_format' => $period->format('Y-m-d'),
                'full_day_display' => $period->format('d.m.y'),
                'short_day' => $period->format('d.m'),
                'week_number' => $period->weekOfYear,
                'is_monday' => $period->isMonday(),
                'month_number' => $period->month,
                'is_first_day_of_month' => $period->isSameDay($period->copy()->startOfMonth()),
                'holidays' => $holidays->map(function ($holiday) {
                    return [
                        'name' => $holiday->name,
                        'type' => $holiday->type,
                        'start_date' => $holiday->startDate,
                        'end_date' => $holiday->endDate,
                        'color' => $holiday->color,
                        'subdivisions' => $holiday->subdivisions->pluck('name'), // Subdivision-Namen sammeln
                    ];
                }),
                //'hours_of_day' => $user->getAttribute('daily_view') ? range(0, 23) : [
            ];
        }

        $months = [];
        foreach ($calendarPeriod as $period) {
            $month = $period->format('m.Y');
            if (!array_key_exists($month, $months)) {
                $months[$month] = [
                    'first_day_in_period' => $period->format('Y-m-d'),
                    'month' => $period->monthName,
                    'year' => $period->format('y'),
                ];
            }
        }

        $dateValue = [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')];
        $calendarType = $startDate->format('d.m.Y') === $endDate->format('d.m.Y') ? 'daily' : 'individual';
        $selectedDate = $startDate->format('Y-m-d') === $endDate->format('Y-m-d') ?
            $startDate->format('Y-m-d') :
            null;
        $roomsWithEvents = empty($room) ?
            $this->eventCollectionService->collectEventsForRooms(
                roomsWithEvents: $this->roomRepository->getFilteredRoomsBy(
                    $calendarFilter?->rooms,
                    $calendarFilter?->room_attributes,
                    $calendarFilter?->areas,
                    $calendarFilter?->room_categories,
                    $calendarFilter?->adjoining_not_loud,
                    $calendarFilter?->adjoining_no_audience,
                    $startDate,
                    $endDate
                ),
                calendarPeriod: $calendarPeriod,
                calendarFilter: $calendarFilter,
                project: $project,
                desiresInventorySchedulingResource: $desiresInventorySchedulingResource
            ) :
            $this->eventCollectionService->collectEventsForRoom(
                room: $room,
                calendarPeriod: $calendarPeriod,
                calendarFilter: $calendarFilter,
                project: $project
            );
        $eventsWithoutRoom = empty($room) ?
            CalendarEventResource::collection(
                $this->eventCollectionService->getEventsWithoutRoom(
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
            [];
        $filterOptions = $this->filterService->getCalendarFilterDefinitions();
        $personalFilters = $this->filterService->getPersonalFilter();

        return [
            'days' => $periodArray,
            'months' => $months,
            'dateValue' => $dateValue,
            'calendarType' => $calendarType,
            'selectedDate' => $selectedDate,
            'roomsWithEvents' => $roomsWithEvents,
            'eventsWithoutRoom' => $eventsWithoutRoom,
            'filterOptions' => $filterOptions,
            'personalFilters' => $personalFilters,
            'user_filters' => $calendarFilter,
        ];
    }


    /**
     * @description Create calendar period DTO
     * @param $startDate
     * @param $endDate
     * @param User $user
     * @param bool $extraRow
     * @return array
     */
    public function createCalendarPeriodDto($startDate, $endDate, User $user, bool $extraRow = true): array
    {
        if (!$startDate || !$endDate) {
            return [];
        }

        $calendarPeriod = CarbonPeriod::create($startDate, $endDate);
        $holidays = collect($this->getHolidaysForPeriod($startDate, $endDate)); // Optimierung: Alle Feiertage einmal abrufen

        $hoursOfDay = $user->getAttribute('daily_view')
            ? array_map(fn($hour) => sprintf('%02d:00', $hour), range(0, 23))
            : [];

        $periodArray = [];

        foreach ($calendarPeriod as $period) {
            $isMonday = $period->isMonday();
            $isSunday = $period->isSunday();
            $isFirstDayOfMonth = $period->isSameDay($period->copy()->firstOfMonth());
            $weekNumber = $period->weekOfYear;
            $monthNumber = $period->month;

            $holidayForDay = $holidays->where('date', $period->toDateString())->values() ?? [];
            if ($extraRow){
                if ($isMonday) {
                    $periodArray[] = [
                        'isExtraRow' => true,
                        'weekNumber' => $period->weekOfYear,
                    ];
                }
            }

            $periodArray[] = new CalendarPeriodDTO(
                day: $period->format('d.m.'),
                dayString: $period->shortDayName,
                isWeekend: $period->isWeekend(),
                fullDay: $period->format('d.m.Y'),
                shortDay: $period->format('d.m'),
                withoutFormat: $period->toDateString(),
                fullDayDisplay: $period->format('d.m.y'),
                weekNumber: $weekNumber,
                isMonday: $isMonday,
                monthNumber: $monthNumber,
                isSunday: $isSunday,
                isFirstDayOfMonth: $isFirstDayOfMonth,
                addWeekSeparator: $isSunday,
                holidays: $holidayForDay,
                hoursOfDay: $hoursOfDay,
                isExtraRow: false,
            );
        }

        return $periodArray;
    }



    /**
     * @description Get holidays for period
     * @param $period
     * @return SupportCollection
     */
    public function getHolidaysForPeriod($period): SupportCollection
    {
        return Holiday::select(['id', 'name', 'date', 'end_date', 'color', 'yearly'])
            ->where(function (Builder $query) use ($period): void {
                $query->where(function (Builder $q) use ($period): void {
                    $q->whereDate('date', '<=', $period->toDateString())
                        ->whereDate('end_date', '>=', $period->toDateString());
                })->orWhere(function (Builder $q) use ($period): void {
                    $q->where('yearly', true)
                        ->whereMonth('date', $period->month)
                        ->whereDay('end_date', $period->day);
                });
            })
            ->with(['subdivisions' => function ($query) {
                $query->select('name');
            }])
            ->get()
            ->transform(fn($holiday) => new CalendarHolidayDTO(
                name: $holiday->name,
                date: $holiday->date->toDateString(),
                end_date: $holiday->end_date->toDateString(),
                color: $holiday->color,
                subdivisions: $holiday->subdivisions->pluck('name')->toArray(),
            ));
    }

    public function getFilteredRooms($filter, $userCalendarSettings, $startDate, $endDate) {
        $userCalendarFilter = $filter;
        $rooms = Room::select(['id', 'name', 'temporary', 'start_date', 'end_date'])
            ->where('relevant_for_disposition', true)
            ->unlessRoomIds($userCalendarFilter?->rooms)
            ->unlessRoomAttributeIds($userCalendarFilter?->room_attributes)
            ->unlessAreaIds($userCalendarFilter?->areas)
            ->unlessRoomCategoryIds($userCalendarFilter?->room_categories)
            ->whenFilterAdjoiningWithStartAndEndDate(
                $userCalendarFilter?->adjoining_not_loud,
                $userCalendarFilter?->adjoining_no_audience,
                $startDate,
                $endDate
            )
            ->when($userCalendarSettings?->hide_unoccupied_rooms, function ($query) use ($filter, $startDate, $endDate) {
                $query->whereExists(function ($eventQuery) use ($filter, $startDate, $endDate) {
                    $eventQuery->selectRaw(1)
                        ->from('events')
                        ->whereColumn('events.room_id', 'rooms.id')
                        ->unless(empty($filter->event_types), function ($q) use ($filter) {
                            $q->whereIn('events.event_type_id', $filter->event_types);
                        })
                        ->where(function ($q) use ($startDate, $endDate) {
                            $q->where(function ($q) use ($startDate, $endDate) {
                                $q->whereBetween('start_time', [$startDate, $endDate])
                                    ->orWhereBetween('end_time', [$startDate, $endDate]);
                            })->orWhere(function ($q) use ($startDate, $endDate) {
                                $q->where('start_time', '<=', $startDate)
                                    ->where('end_time', '>=', $endDate);
                            });
                        });
                });
            })
            ->orderBy('order')
            ->get();

        // Filter out temporary rooms that don't overlap with the displayed time period
        $filteredRooms = $rooms->filter(function ($room) use ($startDate, $endDate) {
            // If the room is not temporary, include it
            if (!$room->temporary) {
                return true;
            }

            // If the room is temporary, check if its time period overlaps with the displayed time period
            return $this->datesOverlap($room->start_date, $room->end_date, $startDate, $endDate);
        });

        return $filteredRooms->map(fn($room) => new RoomDTO(
            id: $room->id,
            name: $room->name,
            has_events: $room->events->isNotEmpty(),
            admins: $room->admins->pluck('id')->toArray()
        ));
    }

    public function getCalendarDateRange(
        UserCalendarSettings $userCalendarSettings,
        UserCalendarFilter|UserShiftCalendarFilter $userCalendarFilter,
        ?Project $project = null
    ): array {
        $today = Carbon::now();
        $useProjectTimePeriod = $userCalendarSettings->getAttribute('use_project_time_period');

        if (!$useProjectTimePeriod && !$project) {
            return $this->userService->getUserCalendarFilterDatesOrDefault($userCalendarFilter);
        }
        if (!$project && $useProjectTimePeriod) {
            $project = $this->projectService->findById($userCalendarSettings->getAttribute('time_period_project_id'));
        }

        return $this->getProjectDateRange($project, $today);
    }

    protected function getProjectDateRange($project, Carbon $today): array
    {
        if (!$project) {
            return [$today->startOfDay(), $today->endOfDay()];
        }

        $firstEvent = $this->projectService->getFirstEventInProject($project);
        $latestEvent = $this->projectService->getLatestEndingEventInProject($project);

        // For events with specific start and end times, add a day to ensure the last day is included
        $endDate = $latestEvent ? $latestEvent->getAttribute('end_time')->copy()->endOfDay() : $today->endOfDay();

        return [
            $firstEvent ? $firstEvent->getAttribute('start_time')->startOfDay() : $today->startOfDay(),
            $endDate,
        ];
    }

    /**
     * Check if two date ranges overlap
     *
     * @param \Carbon\Carbon|null $start1 Start date of first range
     * @param \Carbon\Carbon|null $end1 End date of first range
     * @param \Carbon\Carbon|null $start2 Start date of second range
     * @param \Carbon\Carbon|null $end2 End date of second range
     * @return bool True if the ranges overlap, false otherwise
     */
    private function datesOverlap($start1, $end1, $start2, $end2): bool
    {
        // If any date is null, we can't determine overlap
        if ($start1 === null || $end1 === null || $start2 === null || $end2 === null) {
            return true; // Default to showing the room if dates are missing
        }

        // Check if the ranges overlap
        // Range 1 starts before Range 2 ends AND Range 2 starts before Range 1 ends
        return $start1 <= $end2 && $start2 <= $end1;
    }
}
