<?php

namespace Artwork\Modules\Calendar\Services;


use Artwork\Modules\Calendar\DTO\CalendarHolidayDTO;
use Artwork\Modules\Calendar\DTO\CalendarPeriodDTO;
use Artwork\Modules\Calendar\DTO\EventDTO;
use Artwork\Modules\Calendar\DTO\MinimalEventDTO;
use Artwork\Modules\Calendar\DTO\RoomDTO;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Models\EventStatus;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserCalendarSettings;
use Artwork\Modules\User\Models\UserFilter;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Collection as SupportCollection;

readonly class EventCalendarService
{
    use MapRoomsToContentForCalendar;

    public function filterRoomsEvents(
        SupportCollection $rooms,
        UserFilter $filter,
                   $startDate,
                   $endDate,
        ?UserCalendarSettings $userCalendarSettings = null,
    ): SupportCollection {
        $events = $this->filter(
            $this->getEventQueryWithData(),
            $rooms,
            $filter,
            $startDate,
            $endDate,
            $userCalendarSettings,
        );

        // Key-Sammlungen (einmal pro Response)
        $eventTypeIds   = $events->pluck('event_type_id')->unique();
        $projectIds     = $events->pluck('project_id')->unique()->filter();
        $userIds        = $events->pluck('user_id')->unique()->filter();
        $eventStatusIds = $events->pluck('event_status_id')->unique()->filter();

        $users        = $userIds->isEmpty() ? collect() : User::whereIn('id', $userIds)->select(['id','first_name','last_name','position','email','profile_photo_path'])->get()->keyBy('id');
        $projects     = $projectIds->isEmpty() ? collect() : Project::whereIn('id',$projectIds)->select(['id','name','state','artists','is_group','color','icon'])->with(['status:id,name,color','managerUsers:id,first_name,last_name,position,email,profile_photo_path'])->get()->keyBy('id');
        $eventTypes   = $eventTypeIds->isEmpty() ? collect() : EventType::whereIn('id',$eventTypeIds)->select(['id','name','abbreviation','hex_code'])->get()->keyBy('id');
        $eventStatuses= $eventStatusIds->isEmpty() ? collect() : EventStatus::whereIn('id',$eventStatusIds)->select(['id','color'])->get()->keyBy('id');

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
        SupportCollection $rooms,
        UserFilter $filter,
                   $startDate,
                   $endDate,
        ?UserCalendarSettings $userCalendarSettings = null,
    ): SupportCollection
    {
        $events = $this->filter(
            $this->getEventQueryWithMinimalData(),
            $rooms,
            $filter,
            $startDate,
            $endDate,
            $userCalendarSettings,
        );

        $eventDTOs = $events->map(function ($event) {
            return new MinimalEventDTO(
                id: (int)$event->id,
                start: Carbon::parse($event->start_time)->format('Y-m-d H:i'),
                end:   Carbon::parse($event->end_time)->format('Y-m-d H:i'),
                roomId: (int)$event->room_id,
                daysOfEvent: $event->getAttribute('days_of_event') ?? [],
            );
        })->groupBy('roomId');

        foreach ($rooms as $room) {
            $room->events = $eventDTOs[$room->id] ?? collect();
        }

        return $rooms;
    }

    private function getEventQueryWithData(): Builder
    {
        return Event::query()
            ->select([
                'id','start_time','end_time','eventName','description','project_id','event_type_id','event_status_id',
                'allDay','room_id','user_id','occupancy_option','declined_room_id','is_series','series_id','is_planning'
            ])
            ->with([
                'project:id,name,state,artists',
                'project.status:id,name,color',
                'project.managerUsers:id,first_name,last_name,position,email,profile_photo_path',
                'eventStatus:id,color',
                'event_type:id,name,abbreviation,hex_code',
                'room:id,name',
                'creator:id,first_name,last_name,position,email,profile_photo_path',
                'shifts:id,event_id,start_date,end_date,craft_id'
            ]);
    }

    private function getEventQueryWithMinimalData(): Builder
    {
        return Event::query()->select(['id','start_time','end_time','room_id','user_id','is_planning']);
    }

    private function filter(
        Builder               $eventsQuery,
        SupportCollection     $rooms,
        UserFilter            $filter,
                              $startDate,
                              $endDate,
        ?UserCalendarSettings $userCalendarSettings = null,
    ): SupportCollection
    {
        $endDateEndOfDay = $endDate instanceof Carbon ? $endDate->copy()->endOfDay() : Carbon::parse($endDate)->endOfDay();

        return $eventsQuery
            ->whereIn('room_id', $rooms->pluck('id'))
            ->where(function ($q) use ($startDate, $endDateEndOfDay): void {
                // Überlappungen (Start innerhalb, Ende innerhalb, oder komplett spannend)
                $q->whereBetween('start_time', [$startDate, $endDateEndOfDay])
                    ->orWhereBetween('end_time',   [$startDate, $endDateEndOfDay])
                    ->orWhere(function ($nested) use ($startDate, $endDateEndOfDay) {
                        $nested->where('start_time', '<=', $startDate)
                            ->where('end_time',   '>=', $endDateEndOfDay);
                    });
            })
            ->when(!empty($filter->event_type_ids), fn($q) => $q->whereIn('event_type_id', $filter->event_type_ids))
            ->when(!empty($filter->event_property_ids), function ($q) use ($filter): void {
                $q->whereHas('eventProperties', fn($sub) => $sub->whereIn('event_property_id', $filter->event_property_ids));
            })
            // Planung filtern: Immer echte Events; geplante nur wenn Setting aktiv
            ->where(function ($query) use ($userCalendarSettings): void {
                $query->where('is_planning', false);
                if ($userCalendarSettings?->show_planned_events) {
                    $query->orWhere('is_planning', true);
                }
            })
            ->orderBy('start_time')
            ->get();
    }
}
