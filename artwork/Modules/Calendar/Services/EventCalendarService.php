<?php

namespace Artwork\Modules\Calendar\Services;

use Artwork\Modules\Calendar\DTO\CalendarFrontendDataDTO;
use Artwork\Modules\Calendar\DTO\CalendarRoomDTO;
use Artwork\Modules\Calendar\DTO\EventCalendarDTO;
use Artwork\Modules\Calendar\DTO\EventDTO;
use Artwork\Modules\Calendar\DTO\ProjectDTO;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Models\EventStatus;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\UserCalendarFilter\Models\UserCalendarFilter;
use Artwork\Modules\UserCalendarSettings\Models\UserCalendarSettings;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Auth\AuthManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Psy\Util\Str;

readonly class EventCalendarService
{
    private function buildRoomCacheKey(int $roomId, string $startDate, string $endDate): string
    {
        return 'calendar_room_user_' . auth()->id() . '_room_' . $roomId . '_' . md5($startDate . '_' . $endDate);
    }

    public function __construct(
    )
    {
    }

    public function filterRoomsEvents(
        Collection $rooms,
        $filter,
        $startDate,
        $endDate,
        ?object $userCalendarSettings = null,
    ): Collection {
        $cachedRoomIds = [];

        foreach ($rooms as $room) {
            $cacheKey = $this->buildRoomCacheKey($room->id, $startDate, $endDate);
            if (Cache::has($cacheKey)) {
                logger()->info("HIT: $cacheKey");
            } else {
                logger()->info("MISS: $cacheKey");
            }
            $cachedEvents = Cache::get($cacheKey);
            if ($cachedEvents) {
                $room->events = collect($cachedEvents);
                $cachedRoomIds[] = $room->id;
            }
        }

        // Lade nur Räume, die nicht gecached wurden
        $roomsToQuery = $rooms->filter(fn($room) => !in_array($room->id, $cachedRoomIds, true));
        if ($roomsToQuery->isNotEmpty()) {
            $roomIdsToQuery = $roomsToQuery->pluck('id');

            $events = Event::select([
                'id', 'start_time', 'end_time', 'eventName', 'description', 'project_id',
                'event_type_id', 'event_status_id', 'allDay', 'room_id', 'user_id', 'occupancy_option', 'declined_room_id'
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
                ])
                ->whereIn('room_id', $roomIdsToQuery)
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('start_time', [$startDate, $endDate])
                        ->orWhereBetween('end_time', [$startDate, $endDate])
                        ->orWhere(function ($q) use ($startDate, $endDate) {
                            $q->where('start_time', '<=', $startDate)
                                ->where('end_time', '>=', $endDate);
                        });
                })
                ->orderBy('start_time')
                ->get();

            $eventTypeIds = $events->pluck('event_type_id')->unique();
            $projectIds = $events->pluck('project_id')->unique();
            $userIds = $events->pluck('user_id')->unique();
            $eventStatusIds = $events->pluck('event_status_id')->unique();

            $users = User::whereIn('id', $userIds)->select(['id', 'first_name', 'last_name', 'position', 'email'])->get()->keyBy('id');
            $projects = Project::whereIn('id', $projectIds)
                ->select(['id', 'name', 'state', 'artists', 'is_group', 'color', 'icon'])
                ->with(['status:id,name,color', 'managerUsers:id,first_name,last_name,position,email'])
                ->get()->keyBy('id');
            $eventTypes = EventType::whereIn('id', $eventTypeIds)->select(['id', 'name', 'abbreviation', 'hex_code'])->get()->keyBy('id');
            $eventStatuses = EventStatus::whereIn('id', $eventStatusIds)->select(['id', 'color'])->get()->keyBy('id');

            $eventDTOs = $events->map(fn($event) => EventDTO::fromModel(
                $event,
                $userCalendarSettings,
                $projects,
                $eventTypes,
                $users,
                $eventStatuses
            ))->groupBy('roomId');

            foreach ($roomsToQuery as $room) {
                $key = $this->buildRoomCacheKey($room->id, $startDate, $endDate);
                $room->events = $eventDTOs[$room->id] ?? collect();
                Cache::put($key, $room->events, now()->addMinutes(10));
            }
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
