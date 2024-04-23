<?php

namespace App\Http\Controllers;

use App\Builders\EventBuilder;
use App\Http\Controllers\Calendar\FilterProvider;
use App\Http\Resources\CalendarEventResource;
use App\Models\Freelancer;
use App\Models\ServiceProvider;
use App\Models\User;
use App\Models\UserCalendarFilter;
use App\Models\UserShiftCalendarFilter;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Room\Services\RoomService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 *
 */
class CalendarController extends Controller
{
    protected ?Carbon $startDate = null;

    protected ?Carbon $endDate = null;

    protected Authenticatable|User|null $user;

    protected ?UserCalendarFilter $userCalendarFilter;

    protected ?UserShiftCalendarFilter $userShiftCalendarFilter;


    public function __construct(
        private readonly FilterProvider $filterProvider,
        private readonly RoomService $roomService
    ) {
        //@todo This will break if no user present

        if (!is_null(Auth::user()) || Auth::user() !== false) {
            $this->user = Auth::user();
            $this->userCalendarFilter = $this->user?->calendar_filter;
            $this->userShiftCalendarFilter = $this->user?->shift_calendar_filter;
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function getFilters(): array
    {
        return $this->filterProvider->provide();
    }

    /**
     * @return array<int, Event>
     */
    private function getEventsPerDay($date_of_day, $userId = null): array
    {
        $today = $date_of_day->format('d.m.Y');

        $events = Event::with(
            ['shifts' => function ($query) use ($userId): void {
                $query->whereHas(
                    'users',
                    function ($query) use ($userId): void {
                        $query->where('user_id', $userId);
                    }
                );
            }, 'shifts.shiftsQualifications']
        )->whereHas(
            'shifts.users',
            function ($query) use ($userId): void {
                $query->where('user_id', $userId);
            }
        )->get();

        $eventsToday = $events->filter(
            function ($event) use ($today) {
                return in_array($today, $event->days_of_event);
            }
        )->all();

        return $eventsToday;
    }

    /**
     * @param $date_of_day
     * @param $userId
     * @return array<int, Event>
     */
    private function getShiftsPerDay($date_of_day, $userId = null): array
    {
        $today = $date_of_day->format('d.m.Y');

        $events = Event::with(
            ['shifts' => function ($query) use ($userId): void {
                $query->whereHas(
                    'users',
                    function ($query) use ($userId): void {
                        $query->where('user_id', $userId);
                    }
                );
            }, 'shifts.shiftsQualifications']
        )->whereHas(
            'shifts.users',
            function ($query) use ($userId): void {
                $query->where('user_id', $userId);
            }
        )->get();

        $eventsToday = $events->filter(
            function ($event) use ($today) {
                return in_array($today, $event->days_of_shifts);
            }
        )->all();

        return $eventsToday;
    }


    /**
     * @return array<int, Event>
     */
    private function getEventsPerDayForFreelancer($date_of_day, $freelancerId = null): array
    {
        $eventsToday = [];
        $today = $date_of_day->format('d.m.Y');

        $events = Event::with(
            ['shifts' => function ($query) use ($freelancerId): void {
                $query->whereHas(
                    'freelancer',
                    function ($query) use ($freelancerId): void {
                        $query->where('freelancer_id', $freelancerId);
                    }
                );
            }]
        )
            ->whereHas(
                'shifts.freelancer',
                function ($query) use ($freelancerId): void {
                    $query->where('freelancer_id', $freelancerId);
                }
            )
            ->get();
        foreach ($events as $event) {
            if (in_array($today, $event->days_of_event)) {
                $eventsToday[] = $event;
            }
        }

        return $eventsToday;
    }

    /**
     * @return array<int, Event>
     */
    private function getEventsPerDayForServiceProvider($date_of_day, $serviceProviderId = null): array
    {
        $eventsToday = [];
        $today = $date_of_day->format('d.m.Y');

        $events = Event::with(
            ['shifts' => function ($query) use ($serviceProviderId): void {
                $query->whereHas(
                    'serviceProvider',
                    function ($query) use ($serviceProviderId): void {
                        $query->where('service_provider_id', $serviceProviderId);
                    }
                );
            }]
        )
            ->whereHas(
                'shifts.serviceProvider',
                function ($query) use ($serviceProviderId): void {
                    $query->where('service_provider_id', $serviceProviderId);
                }
            )
            ->get();
        foreach ($events as $event) {
            if (in_array($today, $event->days_of_event)) {
                $eventsToday[] = $event;
            }
        }

        return $eventsToday;
    }

    /**
     * @return array<string, mixed>
     */
    //@todo: fix phpcs error - refactor function because complexity is rising
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
    public function createCalendarData(
        $type = '',
        ?Project $project = null,
        ?Room $room = null,
        $startDate = null,
        $endDate = null,
        ?Authenticatable $user = null
    ): array {
        if (!empty($user)) {
            $this->user = $user;
            $this->userCalendarFilter = $this->user?->calendar_filter;
            $this->userShiftCalendarFilter = $this->user?->shift_calendar_filter;
        }
        $calendarType = 'individual';
        $selectedDate = null;
        if (!is_null($this->userCalendarFilter->start_date) && !is_null($this->userCalendarFilter->end_date)) {
            $this->setDefaultDates();
        } else {
            $this->startDate = Carbon::now()->startOfDay();
            $this->endDate = Carbon::now()->addWeeks()->endOfDay();
        }

        if ($startDate) {
            $this->startDate = Carbon::create($startDate)->startOfDay();
        } else {
            $this->setDefaultDates();
        }
        if ($endDate) {
            $this->endDate = Carbon::create($endDate)->endOfDay();
        } else {
            if ($type === 'dashboard') {
                $this->endDate = Carbon::now()->endOfDay();
            } else {
                $this->setDefaultDates();
            }
        }
        $filterController = new FilterController();

        if (!empty($project)) {
            $firstEventInProject = $project->events()->orderBy('start_time', 'ASC')->limit(1)->first();
            $lastEventInProject = $project->events()->orderBy('end_time', 'DESC')->limit(1)->first();
            if (!empty($firstEventInProject) && !empty($lastEventInProject)) {
                $this->startDate = Carbon::create($firstEventInProject->start_time)->startOfDay();
                $this->endDate = Carbon::create($lastEventInProject->end_time)->endOfDay();
            }
        }
        $startDay = $this->startDate->format('Y-m-d');
        $endDay = $this->endDate->format('Y-m-d');


        if ($startDay && $endDay) {
            if ($startDay !== $endDay) {
                $calendarType = 'individual';
            } else {
                $calendarType = 'daily';
                $selectedDate = $startDay;
            }
        }

        $calendarPeriod = CarbonPeriod::create($this->startDate, $this->endDate);
        $periodArray = [];

        foreach ($calendarPeriod as $period) {
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

        $eventsWithoutRooms = [];

        if (!empty($room)) {
            $better = $this->roomService->collectEventsForRoom($room, $calendarPeriod, $project);
        } else {
            if (!is_null($this->userCalendarFilter->start_date) && !is_null($this->userCalendarFilter->end_date)) {
                $startDate = Carbon::create($this->userCalendarFilter->start_date)->startOfDay();
                $endDate = Carbon::create($this->userCalendarFilter->end_date)->endOfDay();
            } else {
                $startDate = Carbon::now()->startOfDay();
                $endDate = Carbon::now()->addWeeks()->endOfDay();
            }

            // Führe die Abfrage mit den vorbereiteten Beziehungen aus
            $better = $this->filterRooms($startDate, $endDate)->get();

            $better = $this->roomService->collectEventsForRooms($better, $calendarPeriod, $project);

            $events = Event::hasNoRoom()->get();

            $eventsWithoutRooms = CalendarEventResource::collection($events)->resolve();
        }
        return [
            'days' => $periodArray,
            'dateValue' => [$this->startDate->format('Y-m-d'), $this->endDate->format('Y-m-d')],
            // only used for dashboard -> default Dashboard should show Vuecal-Daily calendar with current day
            'calendarType' => $calendarType,
            // Selected Date is needed for change from individual Calendar to VueCal-Daily, so that vuecal knows which
            // date to load
            'selectedDate' => $selectedDate,
            'roomsWithEvents' => $better,
            'eventsWithoutRoom' => $eventsWithoutRooms,
            'filterOptions' => $this->getFilters(),
            'personalFilters' => $filterController->index(),
            'user_filters' => $this->userCalendarFilter,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    //@todo: fix phpcs error - complexity too high
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
    public function createCalendarDataForUserShiftPlan(?User $user = null): array
    {
        $currentDate = Carbon::now();

        if (!is_null($this->userShiftCalendarFilter?->start_date)) {
            $this->startDate = Carbon::parse($this->userShiftCalendarFilter->start_date);
        } else {
            $this->startDate = $currentDate->copy()->startOfWeek()->startOfDay();
        }

        if (!is_null($this->userShiftCalendarFilter?->end_date)) {
            $this->endDate = Carbon::parse($this->userShiftCalendarFilter->end_date);
        } else {
            $this->endDate = $currentDate->copy()->endOfWeek()->endOfDay();
        }

        $calendarPeriod = CarbonPeriod::create($this->startDate, $this->endDate);
        $daysWithEvents = [];
        $totalPlannedWorkingHours = 0;

        foreach ($calendarPeriod as $date) {
            $events = $this->getShiftsPerDay($date, $user->id);

            // Calculate planned working hours for this day
            $plannedWorkingHours = 0;
            $earliestStart = null;
            $latestEnd = null;
            $totalBreakMinutes = 0;

            foreach ($events as $event) {
                $shifts = $event['shifts'];

                foreach ($shifts as $shift) {
                    $start = Carbon::parse($shift['start']);
                    $end = Carbon::parse($shift['end']);
                    $breakMinutes = $shift['break_minutes'];

                    // Update earliest start and latest end
                    if ($earliestStart === null || $start->lt($earliestStart)) {
                        $earliestStart = $start;
                    }
                    if ($latestEnd === null || $end->gt($latestEnd)) {
                        $latestEnd = $end;
                    }

                    // Sum up break minutes
                    $totalBreakMinutes += $breakMinutes;
                }
            }

            // Calculate working hours for the day
            if ($earliestStart !== null && $latestEnd !== null) {
                $totalWorkingMinutes = $earliestStart->diffInMinutes($latestEnd) - $totalBreakMinutes;
                $plannedWorkingHours = max($totalWorkingMinutes / 60, 0);
            }

            $daysWithEvents[$date->format('Y-m-d')] = [
                'day' => $date->format('d.m.'),
                'day_string' => $date->shortDayName,
                'full_day' => $date->format('d.m.Y'),
                'short_day' => $date->format('d.m'),
                'events' => $events,
                'plannedWorkingHours' => $plannedWorkingHours,
                'is_monday' => $date->isMonday(),
                'week_number' => $date->weekOfYear,
            ];
            // Calculate total planned working hours for all days
            $totalPlannedWorkingHours += $plannedWorkingHours;
        }

        return [
            'dateValue' => [$this->startDate->format('Y-m-d'), $this->endDate->format('Y-m-d')],
            'daysWithEvents' => $daysWithEvents,
            'totalPlannedWorkingHours' => $totalPlannedWorkingHours,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    //@todo: fix phpcs error - complexity too high
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
    public function createCalendarDataForFreelancerShiftPlan(?Freelancer $freelancer = null): array
    {
        $currentDate = Carbon::now();
        if (!is_null($this->userShiftCalendarFilter?->start_date)) {
            $this->startDate = Carbon::parse($this->userShiftCalendarFilter->start_date);
        } else {
            $this->startDate = $currentDate->copy()->startOfWeek()->startOfDay();
        }

        if (!is_null($this->userShiftCalendarFilter?->end_date)) {
            $this->endDate = Carbon::parse($this->userShiftCalendarFilter->end_date);
        } else {
            $this->endDate = $currentDate->copy()->endOfWeek()->endOfDay();
        }

        $calendarPeriod = CarbonPeriod::create($this->startDate, $this->endDate);

        $daysWithEvents = [];
        $totalPlannedWorkingHours = 0;

        foreach ($calendarPeriod as $date) {
            $events = $this->getEventsPerDayForFreelancer($date, $freelancer->id);
            // Calculate planned working hours for this day
            $plannedWorkingHours = 0;
            $earliestStart = null;
            $latestEnd = null;
            $totalBreakMinutes = 0;

            foreach ($events as $event) {
                $shifts = $event['shifts'];

                foreach ($shifts as $shift) {
                    $start = Carbon::parse($shift['start']);
                    $end = Carbon::parse($shift['end']);
                    $breakMinutes = $shift['break_minutes'];

                    // Update earliest start and latest end
                    if ($earliestStart === null || $start->lt($earliestStart)) {
                        $earliestStart = $start;
                    }
                    if ($latestEnd === null || $end->gt($latestEnd)) {
                        $latestEnd = $end;
                    }

                    // Sum up break minutes
                    $totalBreakMinutes += $breakMinutes;
                }
            }

            // Calculate working hours for the day
            if ($earliestStart !== null && $latestEnd !== null) {
                $totalWorkingMinutes = $earliestStart->diffInMinutes($latestEnd) - $totalBreakMinutes;
                $plannedWorkingHours = max($totalWorkingMinutes / 60, 0);
            }

            $daysWithEvents[$date->format('Y-m-d')] = [
                'day' => $date->format('d.m.'),
                'day_string' => $date->shortDayName,
                'full_day' => $date->format('d.m.Y'),
                'short_day' => $date->format('d.m'),
                'events' => $events,
                'plannedWorkingHours' => $plannedWorkingHours,
                'is_monday' => $date->isMonday(),
                'week_number' => $date->weekOfYear,
            ];

            // Calculate total planned working hours for all days
            $totalPlannedWorkingHours += $plannedWorkingHours;
        }

        return [
            'dateValue' => [$this->startDate->format('Y-m-d'), $this->endDate->format('Y-m-d')],
            'daysWithEvents' => $daysWithEvents,
            'totalPlannedWorkingHours' => $totalPlannedWorkingHours,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function createCalendarDataForServiceProviderShiftPlan(?ServiceProvider $serviceProvider = null): array
    {
        $currentDate = Carbon::now();
        // Calculate the start of the Monday of the recent calendar week
        if (!is_null($this->userShiftCalendarFilter?->start_date)) {
            $this->startDate = Carbon::parse($this->userShiftCalendarFilter->start_date);
        } else {
            $this->startDate = $currentDate->copy()->startOfWeek()->startOfDay();
        }

        if (!is_null($this->userShiftCalendarFilter?->end_date)) {
            $this->endDate = Carbon::parse($this->userShiftCalendarFilter->end_date);
        } else {
            $this->endDate = $currentDate->copy()->endOfWeek()->endOfDay();
        }

        $calendarPeriod = CarbonPeriod::create($this->startDate, $this->endDate);

        $daysWithEvents = [];
        $totalPlannedWorkingHours = 0;

        foreach ($calendarPeriod as $date) {
            $events = $this->getEventsPerDayForServiceProvider($date, $serviceProvider->id);
            // Calculate planned working hours for this day
            $plannedWorkingHours = 0;

            foreach ($events as $event) {
                $shifts = $event['shifts'];
                foreach ($shifts as $shift) {
                    $start = Carbon::parse($shift['start']);
                    $end = Carbon::parse($shift['end']);

                    $totalWorkingMinutes = 0;
                    $totalWorkingMinutes += $start->diffInMinutes($end);
                    $plannedWorkingHours += max($totalWorkingMinutes / 60, 0);
                }
            }
            $daysWithEvents[$date->format('Y-m-d')] = [
                'day' => $date->format('d.m.'),
                'day_string' => $date->shortDayName,
                'full_day' => $date->format('d.m.Y'),
                'short_day' => $date->format('d.m'),
                'events' => $events,
                'plannedWorkingHours' => $plannedWorkingHours,
                'is_monday' => $date->isMonday(),
                'week_number' => $date->weekOfYear,
            ];
            // Calculate total planned working hours for all days
            $totalPlannedWorkingHours += $plannedWorkingHours;
        }

        return [
            'dateValue' => [$this->startDate->format('Y-m-d'), $this->endDate->format('Y-m-d')],
            'daysWithEvents' => $daysWithEvents,
            'totalPlannedWorkingHours' => $totalPlannedWorkingHours,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function createCalendarDataForShiftPlan(UserShiftCalendarFilter $userShiftCalendarFilter): array
    {
        $selectedDate = null;

        $currentDate = Carbon::now();
        if (!is_null($userShiftCalendarFilter?->start_date)) {
            $this->startDate = Carbon::create($userShiftCalendarFilter->start_date)->startOfDay();
        } else {
            $this->startDate = $currentDate->copy()->startOfWeek()->startOfDay();
        }
        if (!is_null($userShiftCalendarFilter?->end_date)) {
            $this->endDate = Carbon::create($userShiftCalendarFilter->end_date)->endOfDay();
        } else {
            $this->endDate = $currentDate->copy()->endOfWeek()->endOfDay();
        }

        $calendarPeriod = CarbonPeriod::create($this->startDate, $this->endDate);
        $periodArray = [];
        foreach ($calendarPeriod as $period) {
            $periodArray[] = [
                'day' => $period->format('d.m.'),
                'day_string' => $period->shortDayName,
                'is_weekend' => $period->isWeekend(),
                'full_day' => $period->format('d.m.Y'),
                'short_day' => $period->format('d.m'),
                'without_format' => $period->format('Y-m-d'),
                'week_number' => $period->weekOfYear,
                'is_monday' => $period->isMonday(),
            ];
        }

        if (!is_null($this->userCalendarFilter->start_date) && !is_null($this->userCalendarFilter->end_date)) {
            $startDate = Carbon::create($this->userCalendarFilter->start_date)->startOfDay();
            $endDate = Carbon::create($this->userCalendarFilter->end_date)->endOfDay();
        } else {
            $startDate = Carbon::now()->startOfDay();
            $endDate = Carbon::now()->addWeeks()->endOfDay();
        }

        $better = $this->filterRooms($startDate, $endDate, true)->get();
        $better = $this->roomService->collectEventsForRoomsShift($better, $calendarPeriod, null, true);

        return [
            'days' => $periodArray,
            'dateValue' => [$this->startDate->format('Y-m-d'), $this->endDate->format('Y-m-d')],
            'selectedDate' => $selectedDate,
            'roomsWithEvents' => $better,
            'filterOptions' => $this->getFilters(),
            'personalFilters' => (new FilterController())->index(),
            'user_filters' => Auth::user()->shift_calendar_filter()->first(),
        ];
    }

    public function getEventsOfInterval($startDate, $endDate, ?Project $project = null): Collection
    {
        $all_events = Event::query();
        $startDate = Carbon::parse($startDate)->startOfDay();
        $endDate = Carbon::parse($endDate)->endOfDay();

        $filteredEvents = $this->filterEvents($all_events, $startDate, $endDate, null, $project);
        return $filteredEvents->get();
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

    //@todo: fix phpcs error - refactor function because complexity is rising
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
    public function filterEvents(
        $query,
        $startDate,
        $endDate,
        ?Room $room,
        ?Project $project,
        $shiftPlan = false
    ): EventBuilder {
        $user = Auth::user();
        if (!$shiftPlan) {
            $calendarFilter = $user->calendar_filter()->first();
        } else {
            $calendarFilter = $user->shift_calendar_filter()->first();
        }

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
            ->when($project, fn(EventBuilder $builder) => $builder->where('project_id', $project->id))
            ->when($room, fn(EventBuilder $builder) => $builder->where('room_id', $room->id))
            ->unless(
                empty($roomIds) && empty($areaIds) && empty($roomAttributeIds) && empty($roomCategoryIds),
                fn(EventBuilder $builder) => $builder->whereHas(
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
            ->unless(!$hasAudience, fn(EventBuilder $builder) => $builder->where('audience', true))
            ->unless(!$hasNoAudience, fn(EventBuilder $builder) => $builder->where('audience', false))
            ->unless(!$isLoud, fn(EventBuilder $builder) => $builder->where('is_loud', true))
            ->unless(!$isNotLoud, fn(EventBuilder $builder) => $builder->where('is_loud', false))
            ->when($startDate, fn(EventBuilder $builder) => $builder->startAndEndTimeOverlap($startDate, $endDate));
    }

    public function filterRooms($startDate, $endDate, $shiftPlan = false): Builder
    {
        return $this->roomService->filterRooms($startDate, $endDate, $shiftPlan);
    }

    private function checkIfDayWithoutEventsExists($startDate, $endDate): \Illuminate\Database\Eloquent\Collection
    {
        return Event::query()->selectRaw('COUNT(DISTINCT DATE(start_time)) as num_event_days')
            ->where('room_id', 1)
            ->whereRaw(
                "(DATE(start_time) BETWEEN ? AND ? OR DATE(end_time) BETWEEN ? AND ?)",
                [$startDate, $endDate, $startDate, $endDate]
            )
            ->groupByRaw('DATE(start_time)')
            ->havingRaw('COUNT(DISTINCT DATE(start_time)) = 0')
            ->get();
    }

    private function setDefaultDates(): void
    {
        if (!is_null($this->userCalendarFilter->start_date)) {
            $this->startDate = Carbon::create($this->userCalendarFilter->start_date)->startOfDay();
        }
        if (!is_null($this->userCalendarFilter->end_date)) {
            $this->endDate = Carbon::create($this->userCalendarFilter->end_date)->endOfDay();
        }
    }
}
