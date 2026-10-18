<?php

namespace Artwork\Modules\Room\Services;

use App\Http\Resources\MinimalShiftPlanEventResource;
use Artwork\Modules\Area\Models\Area;
use Artwork\Modules\Calendar\Filter\CalendarFilter;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Services\EventCollectionService;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Room\Repositories\RoomRepository;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserCalendarFilter;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Throwable;

readonly class RoomService
{
    public function __construct(
        private RoomRepository $roomRepository,
    ) {
    }

    public function save(Room $room): Room
    {
        /** @var Room $room */
        $room = $this->roomRepository->save($room);

        return $room;
    }

    public function delete(Room $room): bool
    {
        return $this->roomRepository->delete($room);
    }

    public function duplicateByRoomModel(Room $room): Room
    {
        $new_room = $this->duplicateByRoomModelWithoutArea($room);
        $room->area->rooms()->save($new_room);

        return $new_room;
    }

    public function duplicateByRoomModelWithoutArea(Room $room): Room
    {
        $new_room = $room->replicate();
        $new_room->name = __('(Copy)') . ' ' . $room->name;
        $this->roomRepository->save($new_room);

        return $new_room;
    }

    public function getFilteredRooms(
        ?Carbon $startDate,
        ?Carbon $endDate,
        CalendarFilter|null $calendarFilter
    ): EloquentCollection {
        return $this->roomRepository->getFilteredRoomsBy(
            $calendarFilter?->rooms,
            $calendarFilter?->room_attributes,
            $calendarFilter?->areas,
            $calendarFilter?->room_categories,
            $calendarFilter?->adjoining_not_loud,
            $calendarFilter?->adjoining_no_audience,
            $startDate,
            $endDate
        );
    }

    public function deleteAllByArea(Area $area): void
    {
        $this->roomRepository->deleteByReference($area, 'rooms');
    }

    public function getAllWithoutTrashed(array $with = [], array $without = []): EloquentCollection
    {
        return $this->roomRepository->allWithoutTrashed($with, $without);
    }

    /**
     * @return array <string, mixed>
     * @throws Throwable
     * @deprectated use EventCollectionService::collectEventsForRoom
     */
    public function collectEventsForRoom(
        Room $room,
        CarbonPeriod $calendarPeriod,
        ?CalendarFilter $calendarFilter,
        ?Project $project = null
    ): array {
        return app(EventCollectionService::class)->collectEventsForRoom(
            $room,
            $calendarPeriod,
            $calendarFilter,
            $project
        );
    }

    /**
     * @deprecated use EventCollectionService::collectEventsForRoomsOnSpecificDays
     * @return array<string, array<int, array<int, Event>>>
     */
    public function collectEventsForRoomsOnSpecificDays(
        array $desiredRooms,
        array $desiredDays,
        ?CalendarFilter $calendarFilter,
        ?Project $project = null,
    ): array {

        return app(EventCollectionService::class)->collectEventsForRoomsOnSpecificDays(
            $desiredRooms,
            $desiredDays,
            $calendarFilter,
            $project
        );
    }

    /**
     * @return array<string, array<int, array<int, Event>>>
     */
    public function convertEventsForFrontend(
        Room $room,
        array|Collection $events,
        CarbonPeriod $calendarPeriod,
    ): array {
        $actualEvents = [];
        $eventsForRoom = static::fillPeriodWithEmptyEventData($room, $calendarPeriod);

        foreach ($events as $event) {
            $eventStart = $event->start_time->isBefore($calendarPeriod->start) ?
                $calendarPeriod->start :
                $event->start_time;

            $eventEnd = $event->end_time->isAfter($calendarPeriod->end) ?
                $calendarPeriod->end :
                $event->end_time;

            $eventPeriod = CarbonPeriod::create($eventStart->startOfDay(), $eventEnd->endOfDay());

            foreach ($eventPeriod as $date) {
                $dateKey = $date->format('d.m.Y');
                $actualEvents[$dateKey][] = $event;
            }
        }

        foreach ($actualEvents as $key => $value) {
            $eventsForRoom[$key] = [
                'roomName' => $room->getAttribute('name'),
                'roomId' => $room->getAttribute('id'),
                //immediately resolve resource to free used memory
                'events' => MinimalShiftPlanEventResource::collection($value)->resolve()
            ];
        }

        return $eventsForRoom;
    }

    /**
     * @deprecated use EventCollectionService::collectEventsForRooms
     */
    public function collectEventsForRooms(
        array|Collection $roomsWithEvents,
        CarbonPeriod $calendarPeriod,
        ?CalendarFilter $calendarFilter,
        ?Project $project = null
    ): Collection {
        return app(EventCollectionService::class)->collectEventsForRooms(
            $roomsWithEvents,
            $calendarPeriod,
            $calendarFilter,
            $project
        );
    }

    public function collectEventsForRoomsShift(
        array|Collection $rooms,
        array|Collection $events,
        CarbonPeriod $calendarPeriod,
    ): Collection {
        $roomEvents = collect();

        foreach ($rooms as $room) {
            $roomEvents->add(
                $this->convertEventsForFrontend(
                    $room,
                    $events->filter(
                        function ($event) use ($room): bool {
                            return $event->room_id === $room->id;
                        }
                    ),
                    $calendarPeriod,
                )
            );
        }

        return $roomEvents;
    }

    /**
     * @return array<string, mixed>
     */
    public static function fillPeriodWithEmptyEventData(
        Room $room,
        CarbonPeriod $calendarPeriod
    ): array {
        $eventsForRoom = [];
        /** @var Collection $eventsForRoom */
        foreach ($calendarPeriod as $date) {
            $eventsForRoom[$date->format('d.m.Y')] = [
                'roomName' => $room->getAttribute('name'),
                'roomId' => $room->getAttribute('id'),
                'events' => []
            ];
        }
        return $eventsForRoom;
    }

    public function getFallbackRoom(): Room
    {
        if (!$fallbackRoom = $this->roomRepository->getFallbackRoom()) {
            $fallbackRoom = new Room();
            $fallbackRoom->user()->associate(User::first());
            $fallbackRoom->area()->associate(Area::first());
            $fallbackRoom->name = 'FallbackRoom Room';
            $fallbackRoom->description = 'Fallback Room';
            $fallbackRoom->fallback_room = true;
            $fallbackRoom->order = 9999;
            $this->save($fallbackRoom);
        }

        return $fallbackRoom;
    }

    public function findByName(string $name): ?Room
    {
        return $this->roomRepository->findByName($name);
    }

    public function update(Room $room, array $attributes): Room
    {
        $this->roomRepository->update($room, $attributes);

        return $room;
    }
}
