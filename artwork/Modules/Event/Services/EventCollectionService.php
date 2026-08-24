<?php

namespace Artwork\Modules\Event\Services;

use Artwork\Modules\Calendar\Filter\CalendarFilter;
use Artwork\Modules\Event\Http\Resources\MinimalCalendarEventResource;
use Artwork\Modules\Event\Http\Resources\MinimalInventorySchedulingEventResource;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Repositories\EventRepository;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Room\Repositories\RoomRepository;
use Artwork\Modules\Room\Services\RoomService;
use Artwork\Modules\User\Models\UserFilter;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection as SupportCollection;

class EventCollectionService
{
    public function __construct(
        private readonly RoomRepository $roomRepository,
        private readonly EventRepository $eventRepository
    ) {}

    public function collectEventsForRooms(
        array|SupportCollection $roomsWithEvents,
        CarbonPeriod $calendarPeriod,
        ?UserFilter $calendarFilter,
        ?Project $project = null,
        ?bool $desiresInventorySchedulingResource = false
    ): SupportCollection {
        $roomEvents = collect();

        foreach ($roomsWithEvents as $room) {
            $roomEvents->add(
                $this->collectEventsForRoom(
                    room: $room,
                    calendarPeriod: $calendarPeriod,
                    calendarFilter: $calendarFilter,
                    project: $project,
                    desiresInventorySchedulingResource: $desiresInventorySchedulingResource
                )
            );
        }

        return $roomEvents;
    }

    public function collectEventsForRoom(
        Room $room,
        CarbonPeriod $calendarPeriod,
        ?UserFilter $calendarFilter,
        ?Project $project = null,
        ?bool $desiresInventorySchedulingResource = false
    ): array {
        $eventsForRoom = RoomService::fillPeriodWithEmptyEventData($room, $calendarPeriod);
        $actualEvents  = [];

        $query = $this->buildRoomCollectionBaseQuery(
            $room,
            $calendarFilter,
            $project,
            $calendarPeriod,
            null
        );

        foreach ($query->get() as $event) {
            $eventStart = $event->start_time->isBefore($calendarPeriod->start) ? $calendarPeriod->start : $event->start_time;
            $eventEnd   = $event->end_time->isAfter($calendarPeriod->end)     ? $calendarPeriod->end   : $event->end_time;

            $eventPeriod = CarbonPeriod::create($eventStart->copy()->startOfDay(), $eventEnd->copy()->endOfDay());

            foreach ($eventPeriod as $date) {
                $key = $date->format('d.m.Y');
                $actualEvents[$key][] = $event;
            }
        }

        foreach ($actualEvents as $key => $value) {
            $eventsForRoom[$key] = [
                'roomId' => $room->getAttribute('id'),
                'events' => $desiresInventorySchedulingResource
                    ? MinimalInventorySchedulingEventResource::collection($value)->resolve()
                    : MinimalCalendarEventResource::collection($value)->resolve()
            ];
        }

        return $eventsForRoom;
    }

    public function getEventsWithoutRoom(int|Project|null $project = null, array|null $with = null): Collection
    {
        return $this->eventRepository->getEventsWithoutRoom($project, $with);
    }

    public function collectEventsForRoomsOnSpecificDays(
        array $desiredRooms,
        array $desiredDays,
        ?UserFilter $calendarFilter,
        ?Project $project = null,
    ): array {
        $collectedEvents = [];
        if ($desiredRooms === [] || $desiredDays === []) {
            return $collectedEvents;
        }

        $days = [];
        foreach ($desiredDays as $desiredDay) {
            $days[$desiredDay] = Carbon::parse($desiredDay)->toDateString();
        }
        $minDay = min($days);
        $maxDay = max($days);

        // Eine Batch-Query über alle Räume und den gesamten Zeitraum statt einer Query
        // pro Raum × Tag (vorher: 50 Räume × 30 Tage = 1.500 Queries plus Room-Lookups)
        $eventsByRoomId = $this->applyCalendarFilterConditions(
            Event::query()
                ->whereIn('room_id', $desiredRooms)
                ->with($this->calendarEventEagerLoads())
                ->withExists('timelines')
                ->when($project, fn($builder) => $builder->where('project_id', $project->id))
                ->whereDate('start_time', '<=', $maxDay)
                ->whereDate('end_time', '>=', $minDay)
                ->whereNull('deleted_at')
                ->orderBy('start_time'),
            $calendarFilter
        )
            ->get()
            ->groupBy('room_id');

        foreach ($days as $desiredDay => $dayString) {
            foreach ($desiredRooms as $roomId) {
                $eventsOnDay = ($eventsByRoomId->get((int) $roomId) ?? collect())
                    ->filter(
                        static fn (Event $event): bool => $event->start_time->toDateString() <= $dayString
                            && $event->end_time->toDateString() >= $dayString
                    )
                    ->values()
                    ->all();

                $collectedEvents[$desiredDay][$roomId] = MinimalCalendarEventResource::collection(
                    $eventsOnDay
                )->resolve();
            }
        }

        return $collectedEvents;
    }

    /**
     * KORREKT: Relation über $room->events(), nicht getRelation('events')
     */
    private function buildRoomCollectionBaseQuery(
        Room $room,
        ?UserFilter $calendarFilter,
        ?Project $project,
        ?CarbonPeriod $calendarPeriod,
        ?Carbon $date
    ): HasMany {
        $q = $room->events()
            ->with($this->calendarEventEagerLoads())
            ->withExists('timelines')
            ->when($calendarPeriod, function ($builder) use ($calendarPeriod) {
                // Overlap innerhalb Period
                $builder->where(function ($w) use ($calendarPeriod) {
                    $w->whereBetween('start_time', [$calendarPeriod->start, $calendarPeriod->end])
                        ->orWhereBetween('end_time',   [$calendarPeriod->start, $calendarPeriod->end])
                        ->orWhere(function ($nested) use ($calendarPeriod) {
                            $nested->where('start_time', '<=', $calendarPeriod->start)
                                ->where('end_time',   '>=', $calendarPeriod->end);
                        });
                });
            })
            ->when($date, function ($builder) use ($date) {
                $builder->whereDate('start_time', '<=', $date)->whereDate('end_time', '>=', $date);
            })
            ->when($project, fn($builder) => $builder->where('project_id', $project->id))
            ->whereNull('deleted_at')
            ->orderBy('start_time');

        return $this->applyCalendarFilterConditions($q, $calendarFilter);
    }

    /**
     * Kalender-Filter (Räume/Areale/Attribute/Kategorien und Event-Typen) auf eine
     * Event-Query anwenden — gemeinsam genutzt von Einzelraum- und Batch-Pfad.
     */
    private function applyCalendarFilterConditions(
        Builder|HasMany $builder,
        ?UserFilter $calendarFilter
    ): Builder|HasMany {
        $eventTypeIds     = $calendarFilter?->event_type_ids;
        $roomIds          = $calendarFilter?->room_ids;
        $areaIds          = $calendarFilter?->area_ids;
        $roomAttributeIds = $calendarFilter?->room_attribute_ids;
        $roomCategoryIds  = $calendarFilter?->room_category_ids;
        $projectStateIds  = $calendarFilter?->project_state_ids;

        return $builder
            ->unless(
                empty($roomIds) && empty($areaIds) && empty($roomAttributeIds) && empty($roomCategoryIds),
                fn($builder) => $builder->whereHas('room', fn($rb) => $rb
                    ->when($roomIds, fn($rb2) => $rb2->whereIn('rooms.id', $roomIds))
                    ->when($areaIds, fn($rb2) => $rb2->whereIn('area_id', $areaIds))
                    ->when($roomAttributeIds, fn($rb2) => $rb2
                        ->whereHas('attributes', fn($ab) => $ab->whereIn('room_attributes.id', $roomAttributeIds)))
                    ->when($roomCategoryIds, fn($rb2) => $rb2
                        ->whereHas('categories', fn($cb) => $cb->whereIn('room_categories.id', $roomCategoryIds)))
                    ->without('admins'))
            )
            ->when($eventTypeIds, function ($builder) use ($eventTypeIds) {
                $builder->where(function ($b) use ($eventTypeIds) {
                    $b->whereIn('event_type_id', $eventTypeIds)
                        ->orWhereHas('subEvents', fn($sb) => $sb->whereIn('event_type_id', $eventTypeIds));
                });
            })
            // Projektstatus-Filter (zentrale Semantik im Event-Scope)
            ->when($projectStateIds, function ($builder) use ($projectStateIds) {
                $builder->byProjectStateIds($projectStateIds);
            });
    }

    /**
     * Eager Loads für die Kalender-Resources. event_type/eventStatus/eventProperties/series
     * werden von den Resources gelesen und liefen vorher als N+1 pro Event; creator und
     * project.managerUsers werden auf die tatsächlich serialisierten Spalten reduziert.
     * timelines wird nicht mehr voll geladen — hasTimelines kommt aus withExists('timelines').
     */
    private function calendarEventEagerLoads(): array
    {
        return [
            'room' => fn($query) => $query->without(['admins', 'creator']),
            'creator' => fn($query) => $query->select([
                'id',
                'first_name',
                'last_name',
                'profile_photo_path',
                'work_time_balance',
            ]),
            'project',
            'project.managerUsers' => fn($query) => $query->select([
                'users.id',
                'users.first_name',
                'users.last_name',
                'users.profile_photo_path',
                'users.work_time_balance',
            ]),
            'project.status',
            'event_type',
            'eventStatus',
            'eventProperties',
            'series',
            // Die Kalender-Resource braucht von den Schicht-Besetzungen nur Anzahlen —
            // Counts statt voller User-/Freelancer-/Dienstleister-Modelle laden
            'shifts' => fn($query) => $query->withCount(['users', 'freelancer', 'serviceProvider']),
            'shifts.craft',
            'shifts.shiftsQualifications',
            'subEvents.event',
            'subEvents.event.room',
        ];
    }
}
