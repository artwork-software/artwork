<?php

namespace Artwork\Modules\Calendar\Services;

use Artwork\Modules\Calendar\DTO\CalendarFrontendDataDTO;
use Artwork\Modules\Calendar\DTO\CalendarRoomDTO;
use Artwork\Modules\Calendar\DTO\EventCalendarDTO;
use Artwork\Modules\Calendar\DTO\EventDTO;
use Artwork\Modules\Calendar\DTO\MinimalEventDTO;
use Artwork\Modules\Calendar\DTO\ProjectDTO;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Models\EventStatus;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserCalendarFilter;
use Artwork\Modules\User\Models\UserCalendarSettings;
use Artwork\Modules\User\Models\UserFilter;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Psy\Util\Str;

readonly class EventCalendarService
{
    use MapRoomsToContentForCalendar;

    public function filterRoomsEvents(
        Collection $rooms,
        UserFilter $filter,
        $startDate,
        $endDate,
        ?UserCalendarSettings $userCalendarSettings = null,
    ): Collection {
        $events = $this->filter(
            $this->getEventQueryWithData(),
            $rooms,
            $filter,
            $startDate,
            $endDate,
            $userCalendarSettings,
        );
        $eventTypeIds = $events->pluck('event_type_id')->unique();
        $projectIds = $events->pluck('project_id')->unique();
        $userIds = $events->pluck('user_id')->unique();
        $eventStatusIds = $events->pluck('event_status_id')->unique();

        $users = User::whereIn('id', $userIds)->select(['id', 'first_name', 'last_name', 'position', 'email'])->get(
        )->keyBy('id');
        $projects = Project::whereIn('id', $projectIds)
            ->select(['id', 'name', 'state', 'artists', 'is_group', 'color', 'icon'])
            ->with(['status:id,name,color', 'managerUsers:id,first_name,last_name,position,email,profile_photo_path'])
            ->get()->keyBy('id');
        $eventTypes = EventType::whereIn('id', $eventTypeIds)->select(['id', 'name', 'abbreviation', 'hex_code'])->get(
        )->keyBy('id');
        $eventStatuses = EventStatus::whereIn('id', $eventStatusIds)->select(['id', 'color'])->get()->keyBy('id');
        $eventDTOs = $events->map(fn($event) => EventDTO::fromModel(
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

    public function filterRoomsEventsWithMinimalData(
        Collection $rooms,
        UserFilter $filter,
        $startDate,
        $endDate,
        ?UserCalendarSettings $userCalendarSettings = null,
    ): Collection {
        $events = $this->filter(
            $this->getEventQueryWithMinimalData(),
            $rooms,
            $filter,
            $startDate,
            $endDate,
            $userCalendarSettings,
        );
        $eventDTOs = collect();
        foreach ($events as $event) {
            $eventDTOs->push(new MinimalEventDTO(
                id: $event->id,
                start: Carbon::parse($event->start_time)->format('Y-m-d H:i'),
                end: Carbon::parse($event->end_time)->format('Y-m-d H:i'),
                roomId: $event->room_id,
                daysOfEvent: $event->getAttribute('days_of_event') ?? [],
            ));
        }
        $eventDTOs = $eventDTOs->groupBy('roomId');
        foreach ($rooms as $room) {
            $room->events = $eventDTOs[$room->id] ?? collect();
        }
        return $rooms;
    }

    private function getEventQueryWithData(): Builder
    {
        return Event::select([
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
            'is_series',
            'series_id',
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
                'shifts:id,event_id,start_date,end_date'
            ]);
    }

    private function getEventQueryWithMinimalData(): Builder
    {
        return Event::select([
            'id',
            'start_time',
            'end_time',
            'room_id',
            'user_id',
        ]);
    }

    private function filter(
        Builder $eventsQuery,
        Collection $rooms,
        UserFilter $filter,
        $startDate,
        $endDate,
        ?UserCalendarSettings $userCalendarSettings = null,
    ): Collection {
        // Ensure endDate is at the end of the day to include events on the last day
        $endDateEndOfDay = $endDate instanceof Carbon ? $endDate->copy()->endOfDay() : Carbon::parse($endDate)->endOfDay();

        return $eventsQuery
            ->whereIn('room_id', $rooms->pluck('id'))
            ->where(function ($q) use ($startDate, $endDateEndOfDay): void {
                $q->whereBetween('start_time', [$startDate, $endDateEndOfDay])
                    ->orWhereBetween('end_time', [$startDate, $endDateEndOfDay])
                    ->orWhere(function ($q) use ($startDate, $endDateEndOfDay): void {
                        $q->where('start_time', '<=', $startDate)
                            ->where('end_time', '>=', $endDateEndOfDay);
                    });
            })
            ->when(!empty($filter->event_type_ids), fn($q) => $q->whereIn('event_type_id', $filter->event_type_ids))
            ->when(!empty($filter->event_property_ids), function ($q) use ($filter): void {
                $q->whereHas('eventProperties', function ($q) use ($filter): void {
                    $q->whereIn('event_property_id', $filter->event_property_ids);
                });
            })
            ->where(function ($query) use ($userCalendarSettings): void {
                // Always include non-planning events
                $query->where('is_planning', false);

                // If show_planned_events is true, also include planning events
                if ($userCalendarSettings?->show_planned_events) {
                    $query->orWhere('is_planning', true);
                }
            })
            ->orderBy('start_time')
            ->get();
    }
}
