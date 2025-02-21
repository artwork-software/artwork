<?php

namespace Artwork\Modules\Calendar\Services;

use Artwork\Modules\Calendar\DTO\CalendarFrontendDataDTO;
use Artwork\Modules\Calendar\DTO\CalendarRoomDTO;
use Artwork\Modules\Calendar\DTO\EventDTO;
use Artwork\Modules\Calendar\DTO\ShiftDTO;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Models\EventStatus;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\UserCalendarFilter\Models\UserCalendarFilter;
use Artwork\Modules\UserCalendarSettings\Models\UserCalendarSettings;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class ShiftCalendarService
{
    public function filterRoomsEventsAnShifts(
        Collection $rooms,
        UserCalendarFilter $filter,
        $startDate,
        $endDate,
        ?UserCalendarSettings $userCalendarSettings = null,
    ): Collection {
        $roomIds = $rooms->pluck('id');

        $eventWith = [
            'project:id,name,state,artists',
            'project.status:id,name,color',
            'project.managerUsers:id,first_name,last_name,pronouns,position,email_private,email,phone_number,phone_private,description,profile_photo_path',
            'eventStatus:id,color',
            'event_type:id,name,abbreviation,hex_code',
            'room:id,name',
            'creator:id,first_name,last_name,pronouns,position,email_private,email,phone_number,phone_private,description,profile_photo_path',
            'shifts'
        ];


        $events = Event::select([
            'id', 'start_time', 'end_time', 'eventName', 'description', 'project_id',
            'event_type_id', 'event_status_id', 'allDay', 'room_id', 'user_id', 'occupancy_option', 'declined_room_id'
        ])
            ->with($eventWith)
            ->whereIn('room_id', $roomIds)
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_time', [$startDate, $endDate])
                    ->orWhereBetween('end_time', [$startDate, $endDate])
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        $q->where('start_time', '<', $startDate)
                            ->where('end_time', '>', $endDate);
                    });
            })
            ->when(!empty($filter->event_types), fn($query) => $query->whereIn('event_type_id', $filter->event_types))
            ->get();


        $shifts = Shift::whereNull('event_id')->whereIn('room_id', $roomIds)
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        $q->where('start_date', '<', $startDate)
                            ->where('end_date', '>', $endDate);
                    });
            })->get();

        $eventTypeIds = $events->pluck('event_type_id')->unique();
        $projectIds = $events->pluck('project_id')->unique();
        $userIds = $events->pluck('user_id')->unique();
        $eventStatusIds = $events->pluck('event_status_id')->unique();

        $users = User::whereIn('id', $userIds)
            ->select(['id', 'first_name', 'last_name', 'pronouns', 'position', 'email_private', 'email', 'phone_number', 'phone_private', 'description', 'profile_photo_path'])
            ->get()
            ->keyBy('id');

        $projects = Project::whereIn('id', $projectIds)
            ->select(['id', 'name', 'state', 'artists'])
            ->with([
                'status:id,name,color',
                'managerUsers:id,first_name,last_name,pronouns,position,email_private,email,phone_number,phone_private,description,profile_photo_path',
            ])
            ->get()
            ->keyBy('id');

        $eventTypes = EventType::whereIn('id', $eventTypeIds)
            ->select(['id', 'name', 'abbreviation', 'hex_code'])
            ->get()
            ->keyBy('id');

        $eventStatues = EventStatus::whereIn('id', $eventStatusIds)
            ->select(['id', 'color'])
            ->get()
            ->keyBy('id');

        $eventDTOs = $events->map(fn($event) => EventDTO::fromModel(
            $event,
            $userCalendarSettings,
            $projects,
            $eventTypes,
            $users,
            $eventStatues
        ))->groupBy('roomId');

        $shiftDTOs = $shifts->map(fn($shift) => ShiftDTO::fromModel(
            $shift,
            $userCalendarSettings,
            $projects,
            $eventTypes,
            $users,
            $eventStatues
        ))->groupBy('roomId');

        foreach ($rooms as $room) {
            $room->events = $eventDTOs[$room->id] ?? collect();
            $room->shifts = $shiftDTOs[$room->id] ?? collect();
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

            $groupedEvents = $room->events->flatMap(fn($eventDTO) =>
            collect($eventDTO->daysOfEvent)->map(fn($date) => ['date' => $date, 'event' => $eventDTO])
            )->groupBy('date');

            $groupedShifts = $room->shifts->flatMap(fn($shiftDTO) =>
            collect($shiftDTO->daysOfShift)->map(fn($date) => ['date' => $date, 'shift' => $shiftDTO])
            )->groupBy('date');

            foreach ($groupedEvents as $date => $eventsOnDate) {
                if (isset($content[$date])) {
                    $content[$date]['events'] = $eventsOnDate->pluck('event')->all();
                }
            }

            foreach ($groupedShifts as $date => $shiftsOnDate) {
                if (isset($content[$date])) {
                    $content[$date]['shifts'] = $shiftsOnDate->pluck('shift')->all();
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