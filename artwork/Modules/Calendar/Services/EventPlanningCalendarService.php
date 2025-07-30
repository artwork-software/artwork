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
use Artwork\Modules\User\Models\UserCalendarFilter;
use Artwork\Modules\User\Models\UserCalendarSettings;
use Artwork\Modules\User\Models\UserFilter;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

use function PHPUnit\Framework\isFalse;

class EventPlanningCalendarService
{
    use MapRoomsToContentForCalendar;

    public function __construct(
        // private AuthManager         $auth,
        //private CalendarDataService $calendarDataService
    ) {
    }

    public function filterRoomsEvents(
        Collection $rooms,
        UserFilter $filter,
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
            ->where(function ($q) use ($startDate, $endDate): void {
                $q->whereBetween('start_time', [$startDate, $endDate])
                    ->orWhereBetween('end_time', [$startDate, $endDate])
                    ->orWhere(function ($q) use ($startDate, $endDate): void {
                        $q->where('start_time', '<=', $startDate)
                            ->where('end_time', '>=', $endDate);
                    });
            })
            ->unless(empty($filter->event_type_ids), function ($q) use ($filter): void {
                $q->whereIn('event_type_id', $filter->event_type_ids);
            })
            ->unless(empty($filter->event_property_ids), function ($q) use ($filter): void {
                $q->whereHas('eventProperties', function ($q) use ($filter): void {
                    $q->whereIn('event_property_id', $filter->event_property_ids);
                });
            })
            ->where(function ($query) use ($userCalendarSettings): void {
                // Always include planning events
                $query->where('is_planning', true);

                // If show_unplanned_events is true, also include non-planning events
                if ($userCalendarSettings?->show_unplanned_events) {
                    $query->orWhere('is_planning', false);
                }
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
            ->with(['status:id,name,color', 'managerUsers:id,first_name,last_name,position,email,profile_photo_path'])
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
}
