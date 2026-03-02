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
use Artwork\Modules\Room\Services\RoomAttributeService;
use Artwork\Modules\Room\Services\RoomCategoryService;
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
                if ($vacationDay->conflicts->count() > 0 && $currentDate->isSameDay($vacationDay->date)) {
                    $hasConflict = true;
                }
            }

            foreach ($availabilityDays as $availabilityDay) {
                if ($availabilityDay->conflicts->count() > 0 && $currentDate->isSameDay($availabilityDay->date)) {
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
     * Returns events grouped by room, then by date - same structure as BaseCalendar.
     * Structure: [ { roomId, roomName, "dd.mm.YYYY": { events: [...] }, ... }, ... ]
     *
     * @return array<int, array<string, mixed>>
     */
    public function getEventsAtAGlance($startDate, $endDate, ?Project $project = null): array
    {
        $calendarPeriod = CarbonPeriod::create($startDate, $endDate);

        $eventsQuery = $this->filterEvents(Event::query(), $startDate, $endDate, null, $project)
            ->with(
                [
                    'room',
                    'creator',
                    'project',
                    'project.managerUsers',
                    'project.state',
                    'project.categories',
                    'shifts',
                    'shifts.craft',
                    'shifts.users',
                    'shifts.freelancer',
                    'shifts.serviceProvider',
                    'shifts.shiftsQualifications',
                    'subEvents.event',
                    'subEvents.event.room',
                    'timelines'
                ]
            )->orderBy('start_time');

        // Group events by room and date
        $eventsByRoomAndDate = [];
        $roomsData = [];

        foreach ($eventsQuery->get()->all() as $event) {
            $roomId = $event->room_id;
            $room = $event->room;

            if (!$roomId || !$room) {
                continue;
            }

            // Initialize room data if not exists
            if (!isset($roomsData[$roomId])) {
                $roomsData[$roomId] = [
                    'roomId' => $roomId,
                    'roomName' => $room->getAttribute('name'),
                ];
                // Initialize all dates in period with empty events
                foreach ($calendarPeriod as $date) {
                    $dateKey = $date->format('d.m.Y');
                    $roomsData[$roomId][$dateKey] = ['events' => []];
                }
            }

            // Determine which dates this event spans
            $eventStart = $event->start_time->isBefore($calendarPeriod->start) ?
                $calendarPeriod->start :
                $event->start_time;
            $eventEnd = $event->end_time->isAfter($calendarPeriod->end) ?
                $calendarPeriod->end :
                $event->end_time;
            $eventPeriod = CarbonPeriod::create($eventStart->startOfDay(), $eventEnd->endOfDay());

            foreach ($eventPeriod as $date) {
                $dateKey = $date->format('d.m.Y');
                if (!isset($eventsByRoomAndDate[$roomId][$dateKey])) {
                    $eventsByRoomAndDate[$roomId][$dateKey] = [];
                }
                $eventsByRoomAndDate[$roomId][$dateKey][] = $event;
            }
        }

        // Convert events to resources
        foreach ($eventsByRoomAndDate as $roomId => $dates) {
            foreach ($dates as $dateKey => $events) {
                $roomsData[$roomId][$dateKey] = [
                    'events' => MinimalCalendarEventResource::collection($events)->resolve()
                ];
            }
        }

        return array_values($roomsData);
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
        $calendarFilter = $user->userFilters()->calendarFilter()->first();

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
