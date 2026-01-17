<?php

namespace Artwork\Modules\Calendar\Services;

use Artwork\Modules\Calendar\DTO\CalendarFrontendDataDTO;
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
use Artwork\Modules\User\Models\UserFilter;
use Artwork\Modules\User\Services\UserService;
use Artwork\Modules\User\Models\UserCalendarFilter;
use Artwork\Modules\User\Models\UserCalendarSettings;
use Artwork\Modules\User\Models\UserShiftCalendarFilter;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

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

    public function createCalendarData(
        $startDate,
        $endDate,
        $calendarFilter,
        $project = null,
        $room = null,
        $desiresInventorySchedulingResource = null,
        User $user
    ): array {

        // Create the calendar period
        $period = $this->createCalendarPeriodDto($startDate, $endDate, $user, true);

        // Create calendar period for event collection
        $calendarPeriod = CarbonPeriod::create($startDate, $endDate);

        // Get filtered rooms
        $rooms = [];
        if ($room) {
            // If specific room is provided, use only that room
            $rooms = [$room];
        } else {
            // Get filtered rooms based on calendar filter
            $filteredRooms = $this->roomRepository->getFilteredRoomsBy(
                $calendarFilter->room_ids,
                $calendarFilter->room_attribute_ids,
                $calendarFilter->area_ids,
                $calendarFilter->room_category_ids
            );
            $rooms = $filteredRooms->all();
        }

        // Collect events for rooms
        $roomsWithEvents = $this->eventCollectionService->collectEventsForRooms(
            $rooms,
            $calendarPeriod,
            $calendarFilter
        )->toArray();

        // Get events without room
        $eventsWithoutRoom = $this->eventCollectionService->getEventsWithoutRoom(
            $project
        )->toArray();

        // Get filter options and personal filters
        $filterOptions = $this->filterService->getCalendarFilterDefinitions();
        $personalFilters = $this->filterService->getPersonalFilter();

        // convert  $period dto to array
        $period = array_map(fn($d) => is_array($d) ? $d : (array)$d, $period);


        return [
            'days' => $period,
            'dateValue' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d')
            ],
            'calendarType' => $room ? 'room' : 'calendar',
            'selectedDate' => $startDate->format('Y-m-d'),
            'roomsWithEvents' => $roomsWithEvents,
            'eventsWithoutRoom' => $eventsWithoutRoom,
            'filterOptions' => $filterOptions,
            'personalFilters' => $personalFilters,
            'user_filters' => $calendarFilter
        ];
    }

    public function createCalendarPeriodDto($startDate, $endDate, User $user, bool $extraRow = true): array
    {
        if (!$startDate || !$endDate) {
            return [];
        }

        $calendarPeriod = CarbonPeriod::create($startDate, $endDate);

        // Einmal alle Feiertage holen (per-day Filter lokal)
        $holidaysByDate = $this->getHolidaysForRange($startDate, $endDate)
            ->groupBy(fn(CalendarHolidayDTO $h) => $h->date);

        $hoursOfDay = $user->getAttribute('daily_view')
            ? array_map(fn($h) => sprintf('%02d:00', $h), range(0, 23))
            : [];

        $periodArray = [];
        foreach ($calendarPeriod as $period) {
            if ($extraRow && $period->isMonday()) {
                $periodArray[] = [
                    'isExtraRow'  => true,
                    'weekNumber'  => $period->weekOfYear,
                ];
            }

            $periodArray[] = new CalendarPeriodDTO(
                day: $period->format('d.m.'),
                dayString: $period->shortDayName,
                isWeekend: $period->isWeekend(),
                fullDay: $period->format('d.m.Y'),
                shortDay: $period->format('d.m'),
                withoutFormat: $period->toDateString(),
                fullDayDisplay: $period->format('d.m.y'),
                weekNumber: $period->weekOfYear,
                isMonday: $period->isMonday(),
                monthNumber: $period->month,
                isSunday: $period->isSunday(),
                isFirstDayOfMonth: $period->isSameDay($period->copy()->firstOfMonth()),
                addWeekSeparator: $period->isSunday(),
                holidays: $holidaysByDate->get($period->toDateString(), collect())->values(),
                hoursOfDay: $hoursOfDay,
                isExtraRow: false,
            );
        }

        return $periodArray;
    }

    /**
     * Optimierte Room-Liste:
     * - nur benötigte Spalten
     * - `withCount('events')` für effiziente has_events Prüfung ohne N+1
     * - `with('admins')` um N+1 für Admins zu vermeiden
     */
    public function getFilteredRooms(
        ?UserFilter $filter,
        ?UserCalendarSettings $userCalendarSettings,
        CarbonInterface $startDate,
        CarbonInterface $endDate,
        bool $considerShiftsForOccupancy = false,
        ?Project $project = null
    ): SupportCollection {
        $overlap = static function ($q, string $startCol, string $endCol) use ($startDate, $endDate): void {
            // Overlap: start <= endDate AND end >= startDate (SQL-Server indexfreundlich)
            $q->where($startCol, '<=', $endDate)
                ->where($endCol, '>=', $startDate);
        };

        $eventOccupancySubquery = function ($eventQuery) use ($filter, $project, $overlap): void {
            $eventQuery->selectRaw('1')
                ->from('events')
                ->whereColumn('events.room_id', 'rooms.id')
                ->when($project !== null, fn ($q) => $q->where('events.project_id', $project->id))
                ->when(!empty($filter?->event_type_ids), fn ($q) => $q->whereIn('events.event_type_id', $filter->event_type_ids))
                ->where(fn ($q) => $overlap($q, 'events.start_time', 'events.end_time'));

            if (!empty($filter?->event_property_ids)) {
                $ids = $filter->event_property_ids;

                $eventQuery->whereExists(function ($sq) use ($ids) {
                    $sq->selectRaw('1')
                        ->from('event_event_property as eep')
                        ->whereColumn('eep.event_id', 'events.id')
                        ->whereIn('eep.event_property_id', $ids);
                });
            }
        };

        $shiftOccupancySubquery = function ($shiftQuery) use ($filter, $project, $overlap): void {
            $shiftQuery->selectRaw('1')
                ->from('shifts')
                ->whereNull('shifts.event_id')
                ->whereColumn('shifts.room_id', 'rooms.id')
                ->when($project !== null, fn ($q) => $q->where('shifts.project_id', $project->id))
                ->when(!empty($filter?->craft_ids), fn ($q) => $q->whereIn('shifts.craft_id', $filter->craft_ids))
                ->where(fn ($q) => $overlap($q, 'shifts.start_date', 'shifts.end_date'));
        };

        $rooms = Room::query()
            ->select(['id', 'name', 'temporary', 'start_date', 'end_date'])
            ->with(['admins:id,first_name,last_name,profile_photo_path'])
            ->where('relevant_for_disposition', true)
            ->unlessRoomIds($filter?->room_ids)
            ->unlessRoomAttributeIds($filter?->room_attribute_ids)
            ->unlessAreaIds($filter?->area_ids)
            ->unlessRoomCategoryIds($filter?->room_category_ids)
            ->where(function ($q) use ($startDate, $endDate): void {
                $q->where('temporary', false)
                    ->orWhereNull('temporary')
                    ->orWhere(function ($q) use ($startDate, $endDate): void {
                        $q->where('temporary', true)
                            ->where('start_date', '<=', $endDate)
                            ->where('end_date', '>=', $startDate);
                    });
            })
            ->when(
                $userCalendarSettings?->hide_unoccupied_rooms,
                function ($query) use ($eventOccupancySubquery, $shiftOccupancySubquery, $considerShiftsForOccupancy): void {
                    if (!$considerShiftsForOccupancy) {
                        $query->whereExists($eventOccupancySubquery);
                    } else {
                        $query->where(function ($q) use ($eventOccupancySubquery, $shiftOccupancySubquery): void {
                            $q->whereExists($eventOccupancySubquery)
                                ->orWhereExists($shiftOccupancySubquery);
                        });
                    }
                }
            )
            ->orderBy('position')
            ->get();

        return $rooms;
    }


    public function getCalendarDateRange(
        UserCalendarSettings $userCalendarSettings,
        UserFilter $userCalendarFilter,
        ?Project $project = null
    ): array {
        $today = Carbon::now();

        if (!$userCalendarSettings->getAttribute('use_project_time_period')) {
            return $this->userService->getUserCalendarFilterDatesOrDefault($userCalendarFilter);
        }

        if (!$project) {
            $project = $this->projectService->findById($userCalendarSettings->getAttribute('time_period_project_id'));
        }

        return $this->getProjectDateRange($project, $today);
    }

    public function getProjectDateRange($project, Carbon $today): array
    {
        if (!$project) {
            return [$today->startOfDay(), $today->endOfDay()];
        }

        $firstEvent  = $this->projectService->getFirstEventInProject($project);
        $latestEvent = $this->projectService->getLatestEndingEventInProject($project);

        $endDate = $latestEvent ? $latestEvent->getAttribute('end_time')->copy()->endOfDay() : $today->endOfDay();

        return [
            $firstEvent ? $firstEvent->getAttribute('start_time')->startOfDay() : $today->startOfDay(),
            $endDate,
        ];
    }

    private function datesOverlap(?Carbon $start1, ?Carbon $end1, ?Carbon $start2, ?Carbon $end2): bool
    {
        if ($start1 === null || $end1 === null || $start2 === null || $end2 === null) {
            return true;
        }
        return $start1 <= $end2 && $start2 <= $end1;
    }

    /**
     * Feiertage einmal für Range
     * @return SupportCollection<CalendarHolidayDTO>
     */
    private function getHolidaysForRange(Carbon $start, Carbon $end): SupportCollection
    {
        return Holiday::select(['id','name','date','end_date','color','yearly'])
            ->where(function (Builder $q) use ($start, $end): void {
                $q->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                    ->orWhereBetween('end_date', [$start->toDateString(), $end->toDateString()])
                    ->orWhere(function (Builder $nested) use ($start, $end): void {
                        $nested->where('date', '<=', $start->toDateString())
                            ->where('end_date', '>=', $end->toDateString());
                    })
                    ->orWhere(function (Builder $nested) use ($start, $end): void {
                        // jährliche Gedenktage
                        $nested->where('yearly', true)
                            ->whereBetween(\DB::raw('DATE_FORMAT(date, "%m-%d")'), [$start->format('m-d'), $end->format('m-d')]);
                    });
            })
            ->with(['subdivisions' => fn($q) => $q->select('name')])
            ->get()
            ->map(fn($holiday) => new CalendarHolidayDTO(
                name: $holiday->name,
                date: $holiday->date->toDateString(),
                end_date: $holiday->end_date->toDateString(),
                color: $holiday->color,
                subdivisions: $holiday->subdivisions->pluck('name')->toArray(),
            ));
    }

    /**
     * Blendet unbelegte Tage aus dem Kalender aus.
     *
     * - Ermittelt belegte Tage aus allen Räumen (mind. ein Event oder eine Schicht).
     * - Filtert $period auf echte CalendarPeriodDTOs, deren ->fullDay belegt ist.
     * - Reduziert und sortiert pro Raum die content-Keys in der Reihenfolge der gefilterten Perioden.
     * - Entfernt Week-Separators/ExtraRows (die im $period als Arrays vorliegen).
     *
     * @param  CalendarFrontendDataDTO                 $calendarData
     * @param  array<int, CalendarPeriodDTO|array>     $period
     * @return array{
     *     calendarData: CalendarFrontendDataDTO,
     *     period: array<int, CalendarPeriodDTO>
     * }
     */
    public function hideUnoccupiedDays(CalendarFrontendDataDTO $calendarData, array $period): array
    {
        // 1) Belegte Tage sammeln (Set aus "dd.mm.YYYY")
        $occupiedDays = [];

        foreach ($calendarData->rooms as $room) {
            if (!isset($room['content']) || !is_array($room['content'])) {
                continue;
            }

            foreach ($room['content'] as $dateKey => $bucket) {
                $hasEvents = isset($bucket['events']) && !empty($bucket['events']);
                $hasShifts = isset($bucket['shifts']) && !empty($bucket['shifts']);

                if ($hasEvents || $hasShifts) {
                    $occupiedDays[$dateKey] = true;
                }
            }
        }

        // Wenn nichts belegt ist: original zurückgeben (keine Reduktion)
        if (empty($occupiedDays)) {
            // zudem sicherstellen, dass period nur DTOs enthält (optional):
            $periodDTOs = array_values(array_filter(
                $period,
                fn ($d) => $d instanceof CalendarPeriodDTO
            ));

            return [
                'calendarData' => $calendarData,
                'period'       => $periodDTOs,
            ];
        }

        // 2) Perioden filtern: nur echte DTOs, deren ->fullDay im belegten Set liegt
        $filteredPeriod = array_values(array_filter(
            $period,
            static function ($d) use ($occupiedDays) {
                if (!($d instanceof CalendarPeriodDTO)) {
                    return false; // Arrays (Week-Separators/ExtraRows) raus
                }
                return isset($occupiedDays[$d->fullDay]);
            }
        ));

        // 3) Räume-Content auf belegte Tage reduzieren & in Perioden-Reihenfolge sortieren
        $orderedKeys = array_map(static fn (CalendarPeriodDTO $p) => $p->fullDay, $filteredPeriod);

        foreach ($calendarData->rooms as &$room) {
            if (!isset($room['content']) || !is_array($room['content'])) {
                continue;
            }

            // a) nur belegte Keys behalten
            $room['content'] = array_intersect_key($room['content'], $occupiedDays);

            // b) auf Reihenfolge der gefilterten Perioden bringen
            $ordered = [];
            foreach ($orderedKeys as $key) {
                if (isset($room['content'][$key])) {
                    $ordered[$key] = $room['content'][$key];
                }
            }
            $room['content'] = $ordered;
        }
        unset($room);

        return [
            'calendarData' => $calendarData,
            'period'       => $filteredPeriod,
        ];
    }
}
