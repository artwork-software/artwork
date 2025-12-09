<?php

namespace Artwork\Modules\Calendar\Services;

use Antonrom\ModelChangesHistory\Models\Change;
use Artwork\Modules\Calendar\DTO\CalendarFrontendDataDTO;
use Artwork\Modules\Calendar\DTO\CalendarRoomDTO;
use Artwork\Modules\Calendar\DTO\EventDTO;
use Artwork\Modules\Calendar\DTO\EventShiftPlanDTO;
use Artwork\Modules\Calendar\DTO\ShiftDTO;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Models\EventStatus;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserCalendarFilter;
use Artwork\Modules\User\Models\UserCalendarSettings;
use Artwork\Modules\User\Models\UserFilter;
use Artwork\Modules\User\Models\UserShiftCalendarFilter;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class ShiftCalendarService
{
    public function filterRoomsEventsAndShifts(
        Collection $rooms,
        UserFilter $filter,
        CarbonInterface $startDate,
        CarbonInterface $endDate,
        bool $addTimeline = false,
        ?Project $project = null
    ): Collection {
        $roomIds = $rooms->pluck('id')->all();

        // Gemeinsame Overlap-Logik für Zeiträume
        $applyIntervalOverlap = function ($query, string $startColumn, string $endColumn) use ($startDate, $endDate): void {
            $query->whereBetween($startColumn, [$startDate, $endDate])
                ->orWhereBetween($endColumn, [$startDate, $endDate])
                ->orWhere(function ($q) use ($startColumn, $endColumn, $startDate, $endDate): void {
                    $q->where($startColumn, '<', $startDate)
                        ->where($endColumn, '>', $endDate);
                });
        };

        $eventWith = [
            'project:id,name,state,artists,is_group,icon,color',
            'project.status:id,name,color',
            'project.managerUsers:id,first_name,last_name,pronouns,position,email_private,email,phone_number,phone_private,description,profile_photo_path',
            'eventStatus:id,color',
            'event_type:id,name,abbreviation,hex_code',
            'room:id,name',
            'creator:id,first_name,last_name,pronouns,position,email_private,email,phone_number,phone_private,description,profile_photo_path',
            'shifts',
            'eventProperties',
            'verifications' => function ($query): void {
                $query->where('status', 'pending');
            },
        ];

        if ($addTimeline) {
            $eventWith['timelines'] = function ($query): void {
                $query->orderBy('start');
            };
        }

        // Events laden
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
        ])
            ->with($eventWith)
            ->whereIn('room_id', $roomIds)
            ->when($project !== null, fn ($q) => $q->where('project_id', $project->id))
            ->where(function ($query) use ($applyIntervalOverlap): void {
                $applyIntervalOverlap($query, 'start_time', 'end_time');
            })
            ->when(!empty($filter->event_type_ids), fn ($query) => $query->whereIn('event_type_id', $filter->event_type_ids))
            ->get();

        // Standalone Shifts laden
        $shifts = Shift::whereNull('event_id')
            ->whereIn('room_id', $roomIds)
            ->when($project !== null, fn ($q) => $q->where('project_id', $project->id))
            // optional: Filter auf craft_ids analog zu getFilteredRooms
            ->when(!empty($filter->craft_ids), fn ($q) => $q->whereIn('craft_id', $filter->craft_ids))
            ->where(function ($query) use ($applyIntervalOverlap): void {
                $applyIntervalOverlap($query, 'shifts.start_date', 'shifts.end_date');
            })
            ->get();

        // Hilfsdaten für DTOs
        $eventTypeIds = $events->pluck('event_type_id')->unique();
        $userIds      = $events->pluck('user_id')->unique();

        $users = User::whereIn('id', $userIds)
            ->select([
                'id',
                'first_name',
                'last_name',
                'pronouns',
                'position',
                'email_private',
                'email',
                'phone_number',
                'phone_private',
                'description',
                'profile_photo_path',
            ])
            ->get()
            ->keyBy('id');

        $eventTypes = EventType::whereIn('id', $eventTypeIds)
            ->select(['id', 'name', 'abbreviation', 'hex_code'])
            ->get()
            ->keyBy('id');

        // DTOs bauen & pro Raum gruppieren
        $eventDTOs = $events
            ->map(fn ($event) => EventShiftPlanDTO::fromModel(
                $event,
                $eventTypes,
                $users,
                $addTimeline
            ))
            ->groupBy('roomId');

        $shiftDTOs = $shifts
            ->map(fn ($shift) => ShiftDTO::fromModel($shift))
            ->groupBy('roomId');

        // Events & Shifts an Räume hängen
        foreach ($rooms as $room) {
            $room->events = $eventDTOs[$room->id] ?? collect();
            $room->shifts = $shiftDTOs[$room->id] ?? collect();
        }

        return $rooms;
    }


    public function mapRoomsToContentForCalendar(Collection $rooms, $startDate, $endDate): CalendarFrontendDataDTO
    {
        $period = collect(CarbonPeriod::create($startDate, '1 day', $endDate))
            ->mapWithKeys(fn($date) => [$date->format('d.m.Y') => ['events' => [], 'shifts' => []]])
            ->toArray();

        $roomsData = $rooms->map(function ($room) use ($period) {
            $content = $period;

            $groupedEvents = $room->events->flatMap(fn($eventDTO) =>
            collect($eventDTO->daysOfEvent)->map(fn($date) => ['date' => $date, 'event' => $eventDTO]))->groupBy('date');

            $groupedShifts = $room->shifts->flatMap(fn($shiftDTO) =>
            collect($shiftDTO->daysOfShift)->map(fn($date) => ['date' => $date, 'shift' => $shiftDTO]))->groupBy('date');

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

    /**
     * @return array<int, mixed>
     */
    public function getEventShiftsHistoryChanges(): array
    {
        $q = Change::query();
        $q->where('model_type', Shift::class);
        $q->orderBy('created_at', 'desc');
        $historyArray = [];
        $q->get()->each(function (Change $history) use (&$historyArray): void {
            $historyArray[] = [
                'changes' => json_decode($history->changes),
                'created_at' => $history->created_at->diffInHours() < 24
                    ? $history->created_at->diffForHumans()
                    : $history->created_at->format('d.m.Y, H:i'),
            ];
        });

        return $historyArray;
    }
}
