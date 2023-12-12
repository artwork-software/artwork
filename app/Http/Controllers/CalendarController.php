<?php

namespace App\Http\Controllers;

use App\Builders\EventBuilder;
use App\Http\Controllers\Calendar\FilterProvider;
use App\Http\Resources\CalendarEventResource;
use App\Http\Resources\CalendarShowEventResource;
use App\Models\Event;
use App\Models\Freelancer;
use App\Models\Project;
use App\Models\Room;
use App\Models\ServiceProvider;
use App\Models\User;
use App\Models\UserCalendarFilter;
use App\Models\UserCalendarSettings;
use App\Models\UserShiftCalendarFilter;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class CalendarController extends Controller
{
    /**
     * @var Carbon|null
     */
    protected ?Carbon $startDate = null;

    /**
     * @var Carbon|null
     */
    protected ?Carbon $endDate = null;

    /**
     * @var Authenticatable|null
     */
    private ?Authenticatable $user;

    /**
     * @var UserCalendarFilter
     */
    private UserCalendarFilter $userCalendarFilter;

    /**
     * @var UserShiftCalendarFilter
     */
    private UserShiftCalendarFilter $userShiftCalendarFilter;

    /**
     * @var UserCalendarSettings
     */
    private UserCalendarSettings $calendarSettings;

    /**
     * @param FilterProvider $filterProvider
     */
    public function __construct(private readonly FilterProvider $filterProvider)
    {
        //@todo This will break if no user present
        $this->user = Auth::user();
        $this->userCalendarFilter = $this->user->calendar_filter;
        $this->userShiftCalendarFilter = $this->user->shift_calendar_filter;
        $this->calendarSettings = $this->user->calendar_settings;
    }

    /**
     * Returns all fields that can be filtered by in the calendar
     * @return array<string, mixed>
     */
    public function getFilters(): array
    {
        return $this->filterProvider->provide();
    }

    /**
     * @param $date_of_day
     * @param $room
     * @param $projectId
     * @return array<int, Event>
     */
    private function get_events_of_day($date_of_day, $room, $projectId = null): array
    {
        $eventsToday = [];
        $today = $date_of_day->format('d.m.Y');
        foreach ($room->events as $event) {
            if (in_array($today, $event->days_of_event)) {
                if (!empty($projectId)) {
                    if ($event->project_id === $projectId) {
                        $eventsToday[] = $event;
                    }
                } else {
                    $eventsToday[] = $event;
                }
            }
        }

        return $eventsToday;
    }

    /**
     * @param $date_of_day
     * @param $userId
     * @return array<int, Event>
     */
    private function get_events_per_day($date_of_day, $userId = null): array
    {
        $today = $date_of_day->format('d.m.Y');

        $events = Event::with(['shifts' => function ($query) use ($userId): void {
            $query->whereHas('users', function ($query) use ($userId): void {
                $query->where('user_id', $userId);
            });
        }])->whereHas('shifts.users', function ($query) use ($userId): void {
                $query->where('user_id', $userId);
        })->get();

        $eventsToday = $events->filter(function ($event) use ($today) {
            return in_array($today, $event->days_of_event);
        })->all();

        return $eventsToday;
    }

    /**
     * @param $date_of_day
     * @param $freelancerId
     * @return array<int, Event>
     */
    private function get_events_per_day_for_freelancer($date_of_day, $freelancerId = null): array
    {
        $eventsToday = [];
        $today = $date_of_day->format('d.m.Y');

        $events = Event::with(['shifts' => function ($query) use ($freelancerId): void {
            $query->whereHas('freelancer', function ($query) use ($freelancerId): void {
                $query->where('freelancer_id', $freelancerId);
            });
        }])
            ->whereHas('shifts.freelancer', function ($query) use ($freelancerId): void {
                $query->where('freelancer_id', $freelancerId);
            })
            ->get();
        foreach ($events as $event) {
            if (in_array($today, $event->days_of_event)) {
                $eventsToday[] = $event;
            }
        }

        return $eventsToday;
    }

    /**
     * @param $date_of_day
     * @param $serviceProviderId
     * @return array<int, Event>
     */
    private function get_events_per_day_for_service_provider($date_of_day, $serviceProviderId = null): array
    {
        $eventsToday = [];
        $today = $date_of_day->format('d.m.Y');

        $events = Event::with(['shifts' => function ($query) use ($serviceProviderId): void {
            $query->whereHas('service_provider', function ($query) use ($serviceProviderId): void {
                $query->where('service_provider_id', $serviceProviderId);
            });
        }])
            ->whereHas('shifts.service_provider', function ($query) use ($serviceProviderId): void {
                $query->where('service_provider_id', $serviceProviderId);
            })
            ->get();
        foreach ($events as $event) {
            if (in_array($today, $event->days_of_event)) {
                $eventsToday[] = $event;
            }
        }

        return $eventsToday;
    }

    /**
     * @param $type
     * @param Project|null $project
     * @param Room|null $room
     * @param $startDate
     * @param $endDate
     * @return array<string, mixed>
     */
    public function createCalendarData(
        $type = '',
        ?Project $project = null,
        ?Room $room = null,
        $startDate = null,
        $endDate = null
    ): array {
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
            $firstEventInProject = $project->events()->orderBy('start_time', 'ASC')->first();
            $lastEventInProject = $project->events()->orderBy('end_time', 'DESC')->first();
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
                'week_number' => $period->weekOfYear,
                'is_monday' => $period->isMonday(),
            ];
        }

        $eventsWithoutRooms = [];

        if (!empty($room)) {
            $better = collect($calendarPeriod)
                ->mapWithKeys(fn($date) => [
                    $date->format('d.m.') => CalendarEventResource::collection(
                        $this->get_events_of_day($date, $room, $project->id)
                    )
                ]);
        } else {
            if (!is_null($this->userCalendarFilter->start_date) && !is_null($this->userCalendarFilter->end_date)) {
                $startDate = Carbon::create($this->userCalendarFilter->start_date)->startOfDay();
                $endDate = Carbon::create($this->userCalendarFilter->end_date)->endOfDay();
            } else {
                $startDate = Carbon::now()->startOfDay();
                $endDate = Carbon::now()->addWeeks()->endOfDay();
            }

            // Vorbereitung der Beziehungen, die du laden möchtest
            $relations = [
                'events.shifts',
                'events.event_type',
                'events.comments',
                'events.room',
                'events.subEvents',
                'events.series',
                'events.subEvents.type',
                'events.project',
                'events.project.departments',
                'events.project.users',
                'events.project.managerUsers',
                'events.creator',
                'events' => function ($query) use ($project, $room): void {
                    $this->filterEvents($query, null, null, $room, $project)->orderBy('start_time', 'ASC');
                }
            ];

            // Überprüfe, ob work_shifts false ist und entferne 'events.shifts' aus dem Array
            if (!$this->calendarSettings->work_shifts) {
                unset($relations['events.shifts']);
            }

            // Führe die Abfrage mit den vorbereiteten Beziehungen aus
            $better = $this->filterRooms($startDate, $endDate)
                ->with($relations)
                ->get()
                ->map(fn($room) => collect($calendarPeriod)
                    ->mapWithKeys(fn($date) => [
                        $date->format('d.m.') => CalendarEventResource::collection(
                            $this->get_events_of_day($date, $room, $project->id)
                        )
                    ]));

            $events = Event::where('room_id', null)->get();

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
     * @param User|null $user
     * @return array<string, mixed>
     */
    public function createCalendarDataForUserShiftPlan(?User $user = null): array
    {
        $currentDate = Carbon::now();
        // Calculate the start of the Monday of the recent calendar week
        $this->startDate = $currentDate->copy()->startOfWeek()->startOfDay();

        // Calculate the end of the Sunday of the recent calendar week
        $this->endDate = $currentDate->copy()->endOfWeek()->endOfDay();
        $this->setDefaultDates();

        $calendarPeriod = CarbonPeriod::create($this->startDate, $this->endDate);
        $daysWithEvents = [];
        $totalPlannedWorkingHours = 0;

        foreach ($calendarPeriod as $date) {
            $events = $this->get_events_per_day($date, $user->id);

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
     * @param Freelancer|null $freelancer
     * @return array<string, mixed>
     */
    public function createCalendarDataForFreelancerShiftPlan(?Freelancer $freelancer = null): array
    {
        $currentDate = Carbon::now();
        // Calculate the start of the Monday of the recent calendar week
        $this->startDate = $currentDate->copy()->startOfWeek()->startOfDay();

        // Calculate the end of the Sunday of the recent calendar week
        $this->endDate = $currentDate->copy()->endOfWeek()->endOfDay();
        $this->setDefaultDates();

        $calendarPeriod = CarbonPeriod::create($this->startDate, $this->endDate);

        $daysWithEvents = [];
        $totalPlannedWorkingHours = 0;

        foreach ($calendarPeriod as $date) {
            $events = $this->get_events_per_day_for_freelancer($date, $freelancer->id);
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
     * @param ServiceProvider|null $serviceProvider
     * @return array<string, mixed>
     */
    public function createCalendarDataForServiceProviderShiftPlan(?ServiceProvider $serviceProvider = null): array
    {
        $currentDate = Carbon::now();
        // Calculate the start of the Monday of the recent calendar week
        $this->startDate = $currentDate->copy()->startOfWeek()->startOfDay();

        // Calculate the end of the Sunday of the recent calendar week
        $this->endDate = $currentDate->copy()->endOfWeek()->endOfDay();
        $this->setDefaultDates();

        $calendarPeriod = CarbonPeriod::create($this->startDate, $this->endDate);

        $daysWithEvents = [];
        $totalPlannedWorkingHours = 0;

        foreach ($calendarPeriod as $date) {
            $events = $this->get_events_per_day_for_service_provider($date, $serviceProvider->id);
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
     * @param Project|null $project
     * @param Room|null $room
     * @return array<string, mixed>
     */
    public function createCalendarDataForShiftPlan(?Project $project = null, ?Room $room = null): array
    {
        $selectedDate = null;
        $currentDate = Carbon::now();
        // Calculate the start of the Monday of the recent calendar week
        $this->startDate = $currentDate->copy()->startOfWeek()->startOfDay();

        // Calculate the end of the Sunday of the recent calendar week
        $this->endDate = $currentDate->copy()->endOfWeek()->endOfDay();

        $filterController = new FilterController();
        $this->setDefaultDates();

        $calendarPeriod = CarbonPeriod::create($this->startDate, $this->endDate);
        $periodArray = [];

        foreach ($calendarPeriod as $period) {
            $periodArray[] = [
                'day' => $period->format('d.m.'),
                'day_string' => $period->shortDayName,
                'is_weekend' => $period->isWeekend(),
                'full_day' => $period->format('d.m.Y'),
                'without_format' => $period->format('Y-m-d'),
                'week_number' => $period->weekOfYear,
                'is_monday' => $period->isMonday(),
            ];
        }
        if (
            !is_null($this->userShiftCalendarFilter->start_date) &&
            !is_null($this->userShiftCalendarFilter->end_date)
        ) {
            $startDate = Carbon::create($this->userShiftCalendarFilter->start_date)->startOfDay();
            $endDate = Carbon::create($this->userShiftCalendarFilter->end_date)->endOfDay();
        } else {
            $currentDate = Carbon::now();
            // Calculate the start of the Monday of the recent calendar week
            $startDate = $currentDate->copy()->startOfWeek()->startOfDay();

            // Calculate the end of the Sunday of the recent calendar week
            $endDate = $currentDate->copy()->endOfWeek()->endOfDay();
        }

        $better = $this->filterRooms($startDate, $endDate, true)
            ->with([
                'events.event_type',
                'events.comments',
                'events.shifts',
                'events.room',
                'events.subEvents',
                'events.series',
                'events.subEvents.type',
                'events.project',
                'events.project.departments',
                'events.project.users',
                'events.project.managerUsers',
                'events.creator',
                'events' => function ($query) use ($project, $room): void {
                    $this->filterEvents($query, null, null, $room, $project, true)->orderBy('start_time', 'ASC');
                }])
            ->get()
            ->map(fn($room) => collect($calendarPeriod)
                ->mapWithKeys(fn($date) => [
                    $date->format('d.m.') => [
                        'roomName' => $room->name,
                        'events' => CalendarShowEventResource::collection(
                            $this->get_events_of_day($date, $room, $project?->id)
                        )
                    ]
                ]));


        return [
            'days' => $periodArray,
            'dateValue' => [$this->startDate->format('Y-m-d'), $this->endDate->format('Y-m-d')],
            // Selected Date is needed for change from individual Calendar to VueCal-Daily, so that vuecal knows which
            // date to load
            'selectedDate' => $selectedDate,
            'roomsWithEvents' => $better,
            'filterOptions' => $this->getFilters(),
            'personalFilters' => $filterController->index(),
            'user_filters' => Auth::user()->shift_calendar_filter()->first(),
        ];
    }

    /**
     * @return mixed
     */
    public function getEventsOfDay(): Collection
    {
        $all_events = Event::query();
        $filteredEvents = $this->filterEvents($all_events, null, null, null, null);
        return $filteredEvents->get();
    }

    /**
     * @param $startDate
     * @param $endDate
     * @return Collection
     */
    public function getEventsAtAGlance($startDate, $endDate): Collection
    {
        $initialEventQuery = Event::query();

        $filteredEventsQuery = $this->filterEvents($initialEventQuery, $startDate, $endDate, null, null);

        $eventsByRoom = $filteredEventsQuery
            ->with(['room', 'project', 'creator'])
            ->orderBy('start_time', 'ASC')->get();

        return CalendarEventResource::collection($eventsByRoom)->collection->groupBy('room.id');
    }

    /**
     * @param $query
     * @param $startDate
     * @param $endDate
     * @param Room|null $room
     * @param Project|null $project
     * @param $shiftPlan
     * @return mixed
     */
    public function filterEvents(
        $query,
        $startDate,
        $endDate,
        ?Room $room,
        ?Project $project,
        $shiftPlan = false
    ): mixed {
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
                fn(EventBuilder $builder) => $builder->whereHas('room', fn(Builder $roomBuilder) => $roomBuilder
                    ->when($roomIds, fn(Builder $roomBuilder) => $roomBuilder->whereIn('rooms.id', $roomIds))
                    ->when($areaIds, fn(Builder $roomBuilder) => $roomBuilder->whereIn('area_id', $areaIds))
                    ->when($showAdjoiningRooms, fn(Builder $roomBuilder) => $roomBuilder->with('adjoining_rooms'))
                    ->when($roomAttributeIds, fn(Builder $roomBuilder) => $roomBuilder
                        ->whereHas('attributes', fn(Builder $roomAttributeBuilder) => $roomAttributeBuilder
                            ->whereIn('room_attributes.id', $roomAttributeIds)))
                    ->when($roomCategoryIds, fn(Builder $roomBuilder) => $roomBuilder
                        ->whereHas('categories', fn(Builder $roomCategoryBuilder) => $roomCategoryBuilder
                            ->whereIn('room_categories.id', $roomCategoryIds)))
                        ->without(['admins']))
            )
            ->unless(empty($eventTypeIds), function ($builder) use ($eventTypeIds) {
                return $builder->whereIn('event_type_id', array_map('intval', $eventTypeIds))
                    ->orWhereHas('subEvents', function ($subEventBuilder) use ($eventTypeIds): void {
                        $subEventBuilder->whereIn('event_type_id', array_map('intval', $eventTypeIds));
                    });
            })
            ->unless(!$hasAudience, fn(EventBuilder $builder) => $builder->where('audience', true))
            ->unless(!$hasNoAudience, fn(EventBuilder $builder) => $builder->where('audience', false))
            ->unless(!$isLoud, fn(EventBuilder $builder) => $builder->where('is_loud', true))
            ->unless(!$isNotLoud, fn(EventBuilder $builder) => $builder->where('is_loud', false))
            ->when($startDate, fn(EventBuilder $builder) => $builder
                ->where(function ($query) use ($startDate, $endDate): void {
                    // Events, die innerhalb des gegebenen Zeitraums starten und enden
                    $query->whereBetween('start_time', [$startDate, $endDate])
                        ->whereBetween('end_time', [$startDate, $endDate]);
                })
                ->orWhere(function ($query) use ($startDate, $endDate): void {
                    // Events, die vor dem gegebenen Startdatum beginnen und nach dem gegebenen Enddatum enden
                    $query->where('start_time', '<', $startDate)
                        ->where('end_time', '>', $endDate);
                })
                ->orWhere(function ($query) use ($startDate, $endDate): void {
                    // Events, die vor dem gegebenen Startdatum beginnen und innerhalb des gegebenen Zeitraums enden
                    $query->where('start_time', '<', $startDate)
                        ->whereBetween('end_time', [$startDate, $endDate]);
                })
                ->orWhere(function ($query) use ($startDate, $endDate): void {
                    // Events, die innerhalb des gegebenen Zeitraums starten und nach dem gegebenen Enddatum enden
                    $query->whereBetween('start_time', [$startDate, $endDate])
                        ->where('end_time', '>', $endDate);
                }));
    }

    /**
     * @param $startDate
     * @param $endDate
     * @param $shiftPlan
     * @return Builder
     */
    public function filterRooms($startDate, $endDate, $shiftPlan = false): Builder
    {
        $user = Auth::user();
        if (!$shiftPlan) {
            $calendarFilter = $user->calendar_filter()->first();
        } else {
            $calendarFilter = $user->shift_calendar_filter()->first();
        }

        $roomIds = $calendarFilter->rooms ?? null;
        $areaIds = $calendarFilter->areas ?? null;
        $roomAttributeIds = $calendarFilter->room_attributes ?? null;
        $roomCategoryIds = $calendarFilter->room_categories ?? null;
        $adjoiningNoAudience = $calendarFilter->adjoining_no_audience ?? null;
        $adjoiningNotLoud = $calendarFilter->adjoining_not_loud ?? null;

        return Room::query()
            ->unless(
                is_null($roomIds),
                fn(Builder $builder) => $builder->whereIn('id', $roomIds)
            )
            ->unless(
                is_null($roomAttributeIds),
                fn(Builder $builder) => $builder->whereHas(
                    'attributes',
                    function ($query) use ($roomAttributeIds): void {
                        $query->whereIn('room_attributes.id', $roomAttributeIds);
                    }
                )
            )
            ->unless(
                is_null($areaIds),
                fn(Builder $builder) => $builder->whereIn('area_id', $areaIds)
            )
            ->unless(
                is_null($roomCategoryIds),
                fn(Builder $builder) => $builder->whereHas(
                    'categories',
                    function ($query) use ($roomCategoryIds): void {
                        $query->whereIn('room_categories.id', $roomCategoryIds);
                    }
                )
            )
            ->where(
                function ($query) use ($adjoiningNotLoud, $adjoiningNoAudience, $startDate, $endDate): void {
                    $query->where(
                        function ($subQuery) use ($adjoiningNotLoud, $adjoiningNoAudience, $startDate, $endDate): void {
                            $subQuery->unless(
                                is_null($adjoiningNoAudience) && is_null($adjoiningNotLoud),
                                fn(Builder $builder) => $builder
                                    ->whereRelation(
                                        'adjoining_rooms',
                                        function ($adjoining_room_query) use (
                                            $adjoiningNoAudience,
                                            $adjoiningNotLoud,
                                            $startDate,
                                            $endDate
                                        ): void {
                                            $adjoining_room_query->whereRelation(
                                                'events',
                                                function ($event_query) use (
                                                    $adjoiningNoAudience,
                                                    $adjoiningNotLoud,
                                                    $startDate,
                                                    $endDate
                                                ): void {
                                                $event_query
                                                    ->when(
                                                        $startDate,
                                                        fn(Builder $builder) => $builder->whereBetween(
                                                            'start_time',
                                                            [$startDate, $endDate]
                                                        )
                                                    )
                                                    ->when(
                                                        $endDate,
                                                        fn(Builder $builder) => $builder->whereBetween(
                                                            'end_time',
                                                            [$startDate, $endDate]
                                                        )
                                                    )
                                                    ->unless(
                                                        is_null($adjoiningNotLoud),
                                                        fn(Builder $builder) => $builder->where(
                                                            'events.is_loud',
                                                            false
                                                        )
                                                    )
                                                    ->unless(
                                                        is_null($adjoiningNoAudience),
                                                        fn(Builder $builder) => $builder->where(
                                                            'events.audience',
                                                            false
                                                        )
                                                    );
                                                }
                                            );
                                        }
                                    )
                            );
                        }
                    )->orWhereDoesntHave('adjoining_rooms');
                }
            );
    }

    /**
     * @param $startDate
     * @param $endDate
     * @return \Illuminate\Database\Eloquent\Collection
     */
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

    /**
     * @return void
     */
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
