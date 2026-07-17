<?php

namespace Artwork\Modules\Calendar\Services;

use Spatie\Activitylog\Models\Activity;
use Artwork\Modules\Calendar\DTO\CalendarFrontendDataDTO;
use Artwork\Modules\Calendar\DTO\CalendarRoomDTO;
use Artwork\Modules\Calendar\DTO\EventDTO;
use Artwork\Modules\Calendar\DTO\EventShiftPlanDTO;
use Artwork\Modules\Calendar\DTO\ShiftDTO;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Models\EventStatus;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
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
    /**
     * @param object|null $displaySettings user display settings (shift plan / daily view) used to decide
     *                                     which optional project data (artists, status, leaders) gets loaded.
     *                                     null = legacy behavior (status + artists loaded, no leaders).
     * @return array{rooms: Collection, lookups: array}
     */
    public function filterRoomsEventsAndShifts(
        Collection $rooms,
        UserFilter $filter,
        CarbonInterface $startDate,
        CarbonInterface $endDate,
        bool $addTimeline = false,
        ?Project $project = null,
        bool $minimalWorkerData = false,
        ?object $displaySettings = null
    ): array {
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
            'creator:id,first_name,last_name,profile_photo_path',
            'eventProperties:id,name,icon',
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
            ->withExists('timelines')
            ->with($eventWith)
            ->whereIn('room_id', $roomIds)
            ->when($project !== null, fn ($q) => $q->where('project_id', $project->id))
            ->when(!empty($filter->event_type_ids), fn ($q) => $q->whereIn('event_type_id', $filter->event_type_ids))
            ->when(!empty($filter->event_property_ids), function ($q) use ($filter): void {
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
        // Abwesenheiten der zugewiesenen Personen im Zeitraum, damit das
        // is_unavailable-Flag im ShiftDTO ohne N+1-Queries berechnet werden kann
        $vacationsScope = fn ($query) => $query
            ->without(['series', 'conflicts'])
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()]);

        $shiftWorkerWith = $minimalWorkerData
            ? [
                'room:id,name',
                'craft:id,name,abbreviation,color',
                'craft.craftShiftPlaner:id,first_name,last_name,position,business,profile_photo_path',
                'shiftsQualifications',
                'globalQualifications',
                'users:id,first_name,last_name',
                'users.vacations' => $vacationsScope,
                'freelancer:id,first_name,last_name,profile_image',
                'freelancer.vacations' => $vacationsScope,
                'serviceProvider:id,provider_name,profile_image',
                'serviceProvider.vacations' => $vacationsScope,
                'shiftGroup:id,name',
            ]
            : [
                'room:id,name',
                'craft:id,name,abbreviation,color',
                'craft.craftShiftPlaner:id,first_name,last_name,position,business,profile_photo_path',
                'shiftsQualifications',
                'globalQualifications',
                'users:id,first_name,last_name,pronouns,position,profile_photo_path',
                'users.globalQualifications:id',
                'users.vacations' => $vacationsScope,
                'freelancer:id,first_name,last_name,position,profile_image',
                'freelancer.globalQualifications:id',
                'freelancer.vacations' => $vacationsScope,
                'serviceProvider:id,provider_name,profile_image',
                'serviceProvider.globalQualifications:id',
                'serviceProvider.vacations' => $vacationsScope,
                'shiftGroup:id,name',
            ];

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
            ->with($shiftWorkerWith)
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

        // Optionale Projektdaten nur laden, wenn die jeweilige Anzeigeeinstellung aktiv ist
        // (Performance: der Schichtplan soll keine ungenutzten Daten mitschleppen).
        $withArtists = $displaySettings === null || (bool) ($displaySettings->project_artists ?? false);
        $withStatus = $displaySettings === null || (bool) ($displaySettings->project_status ?? false);
        $withLeaders = $displaySettings !== null && (bool) ($displaySettings->project_management ?? false);

        $projectSelect = ['id','name','state','is_group','icon','color'];
        if ($withArtists) {
            $projectSelect[] = 'artists';
        }

        $projectWith = [
            'users:id',
            'groups:id,name,state,is_group,icon,color',
            'groups.users:id',
        ];
        if ($withStatus) {
            $projectWith[] = 'status:id,name,color';
            $projectWith[] = 'groups.status:id,name,color';
        }
        if ($withLeaders) {
            $projectWith['managerUsers'] = fn ($q) => $q->select([
                'users.id',
                'users.first_name',
                'users.last_name',
                'users.position',
                'users.email',
                'users.profile_photo_path',
            ]);
        }

        $projects = Project::query()
            ->select($projectSelect)
            ->with($projectWith)
            ->whereIn('id', $projectIds)
            ->get()
            ->keyBy('id');

        // -------------------------
        // 4) Build lookup maps for normalization
        // -------------------------
        $craftsById = [];
        $eventTypesById = [];
        $projectsById = [];
        $shiftGroupsById = [];

        // Collect crafts from shifts
        foreach ($shifts as $shift) {
            if ($shift->craft && !isset($craftsById[$shift->craft->id])) {
                $craftsById[$shift->craft->id] = self::buildCraftLookupEntry($shift->craft);
            }
            if ($shift->shiftGroup && !isset($shiftGroupsById[$shift->shiftGroup->id])) {
                $shiftGroupsById[$shift->shiftGroup->id] = [
                    'id' => $shift->shiftGroup->id,
                    'name' => $shift->shiftGroup->name,
                ];
            }
        }

        // Collect event types from events
        foreach ($events as $event) {
            if ($event->event_type && !isset($eventTypesById[$event->event_type->id])) {
                $et = $event->event_type;
                $eventTypesById[$et->id] = [
                    'id' => $et->id,
                    'name' => $et->name,
                    'abbreviation' => $et->abbreviation,
                    'hex_code' => $et->hex_code,
                ];
            }
        }

        // Collect projects
        foreach ($projects as $project) {
            $projectsById[$project->id] = self::buildProjectLookupEntry($project);
        }

        $lookups = [
            'craftsById' => $craftsById,
            'eventTypesById' => $eventTypesById,
            'projectsById' => $projectsById,
            'shiftGroupsById' => $shiftGroupsById,
        ];

        // -------------------------
        // 5) DTOs (ohne weitere Queries) — now normalized with IDs only
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

        return ['rooms' => $rooms, 'lookups' => $lookups];
    }



    public function mapRoomsToContentForCalendar(Collection $rooms, $startDate, $endDate): CalendarFrontendDataDTO
    {
        $period = collect(CarbonPeriod::create($startDate, '1 day', $endDate))
            ->mapWithKeys(fn ($date) => [$date->format('d.m.Y') => ['eventIds' => [], 'shiftIds' => []]])
            ->toArray();

        $roomsData = $rooms->map(function (Room $room) use ($period) {
            $content = $period;

            $eventsById = [];
            foreach ($room->events as $eventDTO) {
                $eventsById[$eventDTO->id] = $eventDTO;
            }

            $shiftsById = [];
            foreach ($room->shifts as $shiftDTO) {
                $shiftsById[$shiftDTO->id] = $shiftDTO;
            }

            // Compute days from start/end date ranges for events
            $groupedEventIds = $room->events->flatMap(function ($eventDTO) {
                $days = self::getDaysInRange($eventDTO->start, $eventDTO->end);
                return collect($days)->map(fn ($date) => ['date' => $date, 'id' => $eventDTO->id]);
            })->groupBy('date');

            // Compute days from start/end date ranges for shifts
            $groupedShiftIds = $room->shifts->flatMap(function ($shiftDTO) {
                $days = self::getDaysInRange($shiftDTO->startDate, $shiftDTO->endDate);
                return collect($days)->map(fn ($date) => ['date' => $date, 'id' => $shiftDTO->id]);
            })->groupBy('date');

            foreach ($groupedEventIds as $date => $eventsOnDate) {
                if (isset($content[$date])) {
                    $content[$date]['eventIds'] = $eventsOnDate->pluck('id')->values()->all();
                }
            }

            foreach ($groupedShiftIds as $date => $shiftsOnDate) {
                if (isset($content[$date])) {
                    $content[$date]['shiftIds'] = $shiftsOnDate->pluck('id')->values()->all();
                }
            }

            return new CalendarRoomDTO(
                roomId: $room->id,
                roomName: $room->name,
                content: $content,
                eventsById: $eventsById,
                shiftsById: $shiftsById,
                roomColor: $room->getEffectiveColor(),
            );
        })->toArray();

        return new CalendarFrontendDataDTO(rooms: $roomsData);
    }

    /**
     * Build the normalized project lookup entry used in the shift plan `projectsById` map.
     * Shared by the initial calendar load and the broadcast events so the shape stays in sync.
     *
     * @return array<string, mixed>
     */
    public static function buildProjectLookupEntry(Project $project): array
    {
        $statusModel = $project->relationLoaded('status') ? $project->status : null;
        $groups = $project->relationLoaded('groups') ? $project->groups : collect();

        return [
            'id' => $project->id,
            'name' => $project->name,
            'color' => $project->color,
            'icon' => $project->icon,
            'is_group' => $project->is_group,
            'isInGroup' => $groups->isNotEmpty(),
            'group' => $groups->isNotEmpty() ? $groups->map(fn ($g) => [
                'id' => $g->id,
                'name' => $g->name,
                'color' => $g->color,
            ])->values()->all() : null,
            'status' => $statusModel ? [
                'id' => $statusModel->id,
                'name' => $statusModel->name,
                'color' => $statusModel->color,
            ] : null,
            'artistNames' => $project->artists ?? null,
            // Bewusst ohne E-Mail: Kontaktdaten (inkl. Privacy-Flags) lädt das
            // UserPopoverTooltip lazy über user.tooltip.info nach.
            'leaders' => $project->relationLoaded('managerUsers')
                ? $project->managerUsers->map(fn ($user) => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'position' => $user->position ?? null,
                    'profile_photo_url' => $user->profile_photo_path
                        ? '/storage/' . $user->profile_photo_path
                        : null,
                ])->values()->all()
                : null,
        ];
    }

    /**
     * Build the normalized craft lookup entry used in the shift plan `craftsById` map.
     *
     * @return array<string, mixed>
     */
    public static function buildCraftLookupEntry(Craft $craft): array
    {
        return [
            'id' => $craft->id,
            'name' => $craft->name,
            'abbreviation' => $craft->abbreviation,
            'color' => $craft->color,
            'craft_shift_planer' => $craft->relationLoaded('craftShiftPlaner')
                ? $craft->craftShiftPlaner->map(fn ($user) => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'full_name' => trim($user->first_name . ' ' . $user->last_name),
                    'position' => $user->position ?? null,
                    'business' => $user->business ?? null,
                    'profile_photo_url' => $user->profile_photo_path
                        ? '/storage/' . $user->profile_photo_path
                        : null,
                ])->values()->all()
                : [],
        ];
    }

    /**
     * Compute an array of day strings (dd.mm.YYYY) from a start/end datetime string.
     *
     * @return string[]
     */
    private static function getDaysInRange(string $start, string $end): array
    {
        $startDate = \Carbon\Carbon::parse($start)->startOfDay();
        $endDate = \Carbon\Carbon::parse($end)->startOfDay();
        $days = [];
        while ($startDate->lte($endDate)) {
            $days[] = $startDate->format('d.m.Y');
            $startDate->addDay();
        }
        return $days;
    }

    /**
     * @return array<int, mixed>
     */
    public function getEventShiftsHistoryChanges(): array
    {
        $historyArray = [];

        Activity::query()
            ->where('subject_type', Shift::class)
            ->orderByDesc('created_at')
            ->get()
            ->each(function (Activity $activity) use (&$historyArray): void {
                $properties = $activity->properties;
                $historyArray[] = [
                    'changes' => $properties instanceof \Illuminate\Support\Collection
                        ? $properties->all()
                        : ($properties ?? null),
                    'created_at' => $activity->created_at->diffInHours() < 24
                        ? $activity->created_at->diffForHumans()
                        : $activity->created_at->format('d.m.Y, H:i'),
                ];
            });

        return $historyArray;
    }
}
