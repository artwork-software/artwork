<?php

namespace Artwork\Modules\Calendar\Services;

use Artwork\Modules\Calendar\DTO\CalendarFrontendDataDTO;
use Artwork\Modules\Calendar\DTO\CalendarRoomDTO;
use Artwork\Modules\Calendar\DTO\EventDTO;
use Artwork\Modules\Calendar\DTO\EventDTOWithVerifications;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Models\EventStatus;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\UserCalendarFilter\Models\UserCalendarFilter;
use Artwork\Modules\UserCalendarSettings\Models\UserCalendarSettings;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use function PHPUnit\Framework\isFalse;

class EventPlanningCalendarService
{
    public function __construct(
        // private AuthManager         $auth,
        //private CalendarDataService $calendarDataService
    ) {
    }

    public function filterRoomsEvents(
        Collection $rooms,
        UserCalendarFilter $filter,
        $startDate,
        $endDate,
        ?UserCalendarSettings $userCalendarSettings = null,
    ): Collection {
        $roomIds = $rooms->pluck('id');
        $events = Event::select([
                'id',
                'start_time',
                'end_time',
                'eventName',
                'description',
                'project_id',
                'event_type_id',
                'event_status_id',
                'allDay',
                'room_id',
                'user_id',
                'occupancy_option',
                'declined_room_id',
                'is_planning'
            ])
            ->with([
                'project:id,name,state,artists',
                'project.status:id,name,color',
                'project.managerUsers:id,first_name,last_name,position,email',
                'eventStatus:id,color',
                'event_type:id,name,abbreviation,hex_code',
                'room:id,name',
                'creator:id,first_name,last_name,position,email',
                'shifts:id,event_id,start_date,end_date',
            ])
            ->whereIn('room_id', $roomIds)
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_time', [$startDate, $endDate])
                    ->orWhereBetween('end_time', [$startDate, $endDate])
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        $q->where('start_time', '<=', $startDate)
                            ->where('end_time', '>=', $endDate);
                    });
            })
            ->unless(empty($filter->event_types), function ($q) use ($filter) {
                $q->whereIn('event_type_id', $filter->event_types);
            })
            ->unless(empty($filter->event_properties), function ($q) use ($filter) {
                $q->whereHas('eventProperties', function ($q) use ($filter) {
                    $q->whereIn('event_property_id', $filter->event_properties);
                });
            })
            ->when(!$userCalendarSettings?->show_unplanned_events, function ($q) {
                $q->where('is_planning', true);
            })
            ->orderBy('start_time')
            ->get();

        $eventTypeIds = $events->pluck('event_type_id')->unique();
        $projectIds = $events->pluck('project_id')->unique();
        $userIds = $events->pluck('user_id')->unique();
        $eventStatusIds = $events->pluck('event_status_id')->unique();

        $users = User::whereIn('id', $userIds)
            ->select(['id', 'first_name', 'last_name', 'position', 'email'])
            ->get()->keyBy('id');

        $projects = Project::whereIn('id', $projectIds)
            ->select(['id', 'name', 'state', 'artists', 'is_group', 'color', 'icon'])
            ->with(['status:id,name,color', 'managerUsers:id,first_name,last_name,position,email'])
            ->get()->keyBy('id');

        $eventTypes = EventType::whereIn('id', $eventTypeIds)
            ->select(['id', 'name', 'abbreviation', 'hex_code'])
            ->get()
            ->keyBy('id');

        $eventStatuses = EventStatus::whereIn('id', $eventStatusIds)
            ->select(['id', 'color'])
            ->get()
            ->keyBy('id');

        $eventDTOs = $events->map(fn($event) => EventDTOWithVerifications::fromModel(
            $event,
            $userCalendarSettings,
            $projects,
            $eventTypes,
            $users,
            $eventStatuses
        ))->groupBy('roomId');

        foreach ($rooms as $room) {
            $room->events = $eventDTOs[$room->id] ?? collect();
        }

        return $rooms;
    }


    public function mapRoomsToContentForCalendar(Collection $rooms, $startDate, $endDate): CalendarFrontendDataDTO
    {
        $period = collect(CarbonPeriod::create($startDate, '1 day', $endDate))
            ->mapWithKeys(fn($date) => [$date->format('d.m.Y') => ['events' => []]])
            ->toArray();

        $roomsData = $rooms->map(function ($room) use ($period) {
            $content = $period;

            $groupedEvents = $room->events->flatMap(
                fn($eventDTO) => collect($eventDTO->daysOfEvent)->map(
                    fn($date) => ['date' => $date, 'event' => $eventDTO]
                )
            )->groupBy('date');

            foreach ($groupedEvents as $date => $eventsOnDate) {
                if (isset($content[$date])) {
                    $content[$date]['events'] = $eventsOnDate->pluck('event')->all();
                }
            }

            return new CalendarRoomDTO(
                roomId: $room->id,
                roomName: $room->name,
                content: $content
            );
        })->toArray();

        return new CalendarFrontendDataDTO(rooms: $roomsData);
    }
}