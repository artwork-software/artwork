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
        $roomIds = $rooms->modelKeys();

        $overlap = static function ($q, string $startCol, string $endCol) use ($startDate, $endDate): void {
            $q->where($startCol, '<=', $endDate)
                ->where($endCol, '>=', $startDate);
        };

        // -------------------------
        // 1) Events (minimal + eager)
        // -------------------------
        $eventWith = [
            'eventStatus:id,color',
            'event_type:id,name,abbreviation,hex_code',
            'room:id,name',
            'creator:id,first_name,last_name,pronouns,position,email_private,email,phone_number,phone_private,description,profile_photo_path',
            'eventProperties:id,name,icon', // ✅ minimal!
        ];

        if ($addTimeline) {
            $eventWith['timelines'] = fn ($q) => $q->orderBy('start');
            // Optional: ->select(['id','event_id','start','end', ...]) wenn du Spalten kennst
        }

        $events = Event::query()
            ->select([
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
            ->when(!empty($filter->event_type_ids), fn ($q) => $q->whereIn('event_type_id', $filter->event_type_ids))
            ->when(!empty($filter->event_property_ids), function ($q) use ($filter) {
                $ids = $filter->event_property_ids;

                // Variante A (sauber & nutzt die Relation)
                $q->whereHas('eventProperties', fn ($p) => $p->whereIn('event_properties.id', $ids));
                // Achtung: Table-Name event_properties ist Standard. Falls bei euch anders: anpassen.
            })
            ->where(fn ($q) => $overlap($q, 'start_time', 'end_time'))
            ->orderBy('start_time')
            ->get();

        // -------------------------
        // 2) Standalone Shifts (eager alles was DTO braucht)
        // -------------------------
        $shifts = Shift::query()
            ->select([
                'id',
                'start_date',
                'end_date',
                'start',
                'end',
                'break_minutes',
                'event_id',
                'description',
                'craft_id',
                'room_id',
                'project_id',
                'is_committed',
                'in_workflow',
                'shift_group_id',
            ])
            ->whereNull('event_id')
            ->whereIn('room_id', $roomIds)
            ->when($project !== null, fn ($q) => $q->where('project_id', $project->id))
            ->when(!empty($filter->craft_ids), fn ($q) => $q->whereIn('craft_id', $filter->craft_ids))
            ->where(fn ($q) => $overlap($q, 'start_date', 'end_date'))
            ->with([
                'room:id,name',
                'craft:id,name',                 // + benötigte Felder
                'craft.qualifications:id,name',  // wenn Frontend es braucht
                'shiftsQualifications',          // ggf. später: select-minimal
                'users:id,first_name,last_name,pronouns,position,profile_photo_path',
                'users.globalQualifications:id',
                'freelancer:id,first_name,last_name,position,profile_image',
                'freelancer.globalQualifications:id',
                'serviceProvider:id,provider_name,profile_image',
                'serviceProvider.globalQualifications:id',
                'shiftGroup:id,name',
                'craft.craftShiftPlaner'
            ])
            ->orderBy('start_date', 'ASC')
            ->get();

        // -------------------------
        // 3) Projekte NUR für das Ergebnis laden (statt alle 809)
        // -------------------------
        $projectIds = $events->pluck('project_id')
            ->merge($shifts->pluck('project_id'))
            ->filter()
            ->unique()
            ->values();

        $projects = Project::query()
            ->select(['id','name','state','artists','is_group','icon','color'])
            ->with([
                'status:id,name,color',
                'managerUsers:id,first_name,last_name,pronouns,position,email_private,email,phone_number,phone_private,description,profile_photo_path',
                'users:id',
                'groups:id,name,state,artists,is_group,icon,color',
                'groups.status:id,name,color',
                'groups.managerUsers:id,first_name,last_name,pronouns,position,email_private,email,phone_number,phone_private,description,profile_photo_path',
                'groups.users:id',
            ])
            ->whereIn('id', $projectIds)
            ->get()
            ->keyBy('id');

        // -------------------------
        // 4) DTOs (ohne weitere Queries)
        // -------------------------
        $eventDTOs = $events
            ->map(fn ($event) => EventShiftPlanDTO::fromModel(
                $event,
                $projects->get($event->project_id),
                $addTimeline
            ))
            ->groupBy('roomId');

        $shiftDTOs = $shifts
            ->map(fn ($shift) => ShiftDTO::fromModel(
                $shift,
                $projects->get($shift->project_id)
            ))
            ->groupBy('roomId');

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
