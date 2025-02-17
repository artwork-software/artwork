<?php

namespace Artwork\Modules\Calendar\Services;

use App\Http\Resources\MinimalShiftPlanShiftResource;
use Artwork\Modules\Calendar\DTO\CalendarFrontendDataDTO;
use Artwork\Modules\Calendar\DTO\CalendarRoomDTO;
use Artwork\Modules\Calendar\DTO\EventCalendarDTO;
use Artwork\Modules\Calendar\DTO\EventDTO;
use Artwork\Modules\Calendar\DTO\ProjectDTO;
use Artwork\Modules\Event\Models\Event;
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
use Psy\Util\Str;

readonly class EventCalendarService
{
    public function __construct(
        // private AuthManager         $auth,
        //private CalendarDataService $calendarDataService
    ) {
    }


    public function filterRoomsEventsAndShifts(
        Collection $rooms,
        UserCalendarFilter $filter,
        $startDate,
        $endDate,
        ?UserCalendarSettings $userCalendarSettings = null,
    ): Collection {
        $roomIds = $rooms->pluck('id');

        $events = Event::select([
            'id', 'start_time', 'end_time', 'eventName', 'description', 'project_id',
            'event_type_id', 'event_status_id', 'allDay', 'room_id', 'user_id', 'occupancy_option', 'declined_room_id'
        ])
            ->with([
                'project:id,name,state,artists',
                'project.status:id,name,color',
                'project.managerUsers:id,first_name,last_name,pronouns,position,email_private,email,phone_number,phone_private,description,profile_photo_path',
                'eventStatus:id,color',
                'event_type:id,name,abbreviation,hex_code',
                'room:id,name',
                'creator:id,first_name,last_name,pronouns,position,email_private,email,phone_number,phone_private,description,profile_photo_path',
                'shifts' => fn($query) => $query->select(['id', 'start_date', 'end_date', 'event_id'])
            ])
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

        /** @var UserCalendarSettings $userCalendarSettings */
        $showProjectStatus = $userCalendarSettings->project_status;
        $showProjectArtists = $userCalendarSettings->project_artists;
        $showProjectLeaders = $userCalendarSettings->project_management;

        $eventTypeIds = $events->pluck('event_type_id')->unique();
        $eventTypes = EventType::whereIn('id', $eventTypeIds)
            ->select(['id', 'name', 'abbreviation', 'hex_code'])
            ->get()
            ->keyBy('id');

        $eventDTOs = $events->map(fn($event) => new EventDTO(
            id: $event->id,
            start: Carbon::parse($event->start_time)->format('Y-m-d H:i'),
            end: Carbon::parse($event->end_time)->format('Y-m-d H:i'),
            eventName: $event->eventName,
            description: $event->description,
            project: $event->project ? new ProjectDTO(
                id: $event->project->id,
                name: $event->project->name,
                statusId: $showProjectStatus ? $event->project->state : null,
                backgroundColor: $showProjectStatus ? $event->project->status?->color . '33' : null,
                borderColor: $showProjectStatus ? $event->project->status?->color : null,
                statusName: $showProjectStatus ? $event->project->status?->name : null,
                artistNames: $showProjectArtists && filled($event->project?->artists)
                    ? $event->project?->artists
                    : null,
                leaders: $showProjectLeaders && filled($event->project?->managerUsers)
                    ? $event->project?->managerUsers
                    : null,
            ) : null,
            eventTypeId: $event->event_type_id,
            eventTypeName: $eventTypes[$event->event_type_id]?->name,
            eventTypeAbbreviation: $eventTypes[$event->event_type_id]?->abbreviation,
            eventTypeColor: $eventTypes[$event->event_type_id]?->hex_code,
            eventStatusId: $event->event_status_id,
            eventStatusColor: $event->eventStatus?->color,
            shifts: $userCalendarSettings->work_shifts ?
                MinimalShiftPlanShiftResource::collection($event->shifts)->resolve() :
                null,
            allDay: $event->allDay,
            roomId: $event->room_id,
            roomName: $event->room->name,
            daysOfEvent: $event->getAttribute('days_of_event') ?? [],
            startHour: $event->getAttribute('start_hour') ?? 0,
            minutesFormStartHourToStart: $event->getAttribute('minutes_form_start_hour_to_start') ?? 0,
            eventLengthInHours: $event->getAttribute('event_length_in_hours') ?? 0,
            created_by: $event?->creator,
            formattedDates: $event->getAttribute('formatted_dates') ?? [],
            is_series: $event->is_series,
            eventProperties: $event->eventProperties,
            occupancy_option: $event->occupancy_option,
            declinedRoomId: $event->declined_room_id,
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

            $groupedEvents = $room->events->flatMap(fn($eventDTO) =>
            collect($eventDTO->daysOfEvent)->map(fn($date) => ['date' => $date, 'event' => $eventDTO])
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