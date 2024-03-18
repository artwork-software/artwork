<?php

namespace Artwork\Modules\Room\Services;

use App\Builders\EventBuilder;
use App\Enums\NotificationConstEnum;
use App\Http\Resources\CalendarShowEventResource;
use App\Models\User;
use App\Support\Services\NewHistoryService;
use App\Support\Services\NotificationService;
use Artwork\Modules\Area\Models\Area;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Room\Repositories\RoomRepository;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class RoomService
{
    public function __construct(
        private readonly RoomRepository $roomRepository,
        private readonly NotificationService $notificationService,
        private readonly NewHistoryService $history
    ) {
        $this->history->setModel(Room::class);
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
        $new_room->name = '(Kopie) ' . $room->name;
        $this->roomRepository->save($new_room);

        return $new_room;
    }

    public function filterRooms($startDate, $endDate, $shiftPlan = false): Builder
    {
        $user = Auth::user();
        if (!$shiftPlan) {
            $calendarFilter = $user->calendar_filter()->first();
        } else {
            $calendarFilter = $user->shift_calendar_filter()->first();
        }

        $roomIds = $calendarFilter->rooms ?? null;
        $areaIds = $calendarFilter->areas ?? null;
        $roomAttributeIds = $calendarFilter->room_attributes ?? null;
        $roomCategoryIds = $calendarFilter->room_categories ?? null;
        $adjoiningNoAudience = $calendarFilter->adjoining_no_audience ?? null;
        $adjoiningNotLoud = $calendarFilter->adjoining_not_loud ?? null;

        return Room::query()
            ->unless(
                is_null($roomIds),
                fn(Builder $builder) => $builder->whereIn('id', $roomIds)
            )
            ->unless(
                is_null($roomAttributeIds),
                fn(Builder $builder) => $builder->whereHas(
                    'attributes',
                    function ($query) use ($roomAttributeIds): void {
                        $query->whereIn('room_attributes.id', $roomAttributeIds);
                    }
                )
            )
            ->unless(
                is_null($areaIds),
                fn(Builder $builder) => $builder->whereIn('area_id', $areaIds)
            )
            ->unless(
                is_null($roomCategoryIds),
                fn(Builder $builder) => $builder->whereHas(
                    'categories',
                    function ($query) use ($roomCategoryIds): void {
                        $query->whereIn('room_categories.id', $roomCategoryIds);
                    }
                )
            )
            ->where(
                function ($query) use ($adjoiningNotLoud, $adjoiningNoAudience, $startDate, $endDate): void {
                    $query->where(
                        function ($subQuery) use ($adjoiningNotLoud, $adjoiningNoAudience, $startDate, $endDate): void {
                            $subQuery->unless(
                                is_null($adjoiningNoAudience) && is_null($adjoiningNotLoud),
                                fn(Builder $builder) => $builder
                                    ->whereRelation(
                                        'adjoining_rooms',
                                        function ($adjoining_room_query) use (
                                            $adjoiningNoAudience,
                                            $adjoiningNotLoud,
                                            $startDate,
                                            $endDate
                                        ): void {
                                            $adjoining_room_query->whereRelation(
                                                'events',
                                                function ($event_query) use (
                                                    $adjoiningNoAudience,
                                                    $adjoiningNotLoud,
                                                    $startDate,
                                                    $endDate
                                                ): void {
                                                    $event_query
                                                        ->when(
                                                            $startDate,
                                                            fn(Builder $builder) => $builder->whereBetween(
                                                                'start_time',
                                                                [$startDate, $endDate]
                                                            )
                                                        )
                                                        ->when(
                                                            $endDate,
                                                            fn(Builder $builder) => $builder->whereBetween(
                                                                'end_time',
                                                                [$startDate, $endDate]
                                                            )
                                                        )
                                                        ->unless(
                                                            is_null($adjoiningNotLoud),
                                                            fn(Builder $builder) => $builder->where(
                                                                'events.is_loud',
                                                                false
                                                            )
                                                        )
                                                        ->unless(
                                                            is_null($adjoiningNoAudience),
                                                            fn(Builder $builder) => $builder->where(
                                                                'events.audience',
                                                                false
                                                            )
                                                        );
                                                }
                                            );
                                        }
                                    )
                            );
                        }
                    )->orWhereDoesntHave('adjoining_rooms');
                }
            )->orderBy('order', 'DESC');
    }

    public function createByRequest(Request $request): Room
    {
        $room = new Room();
        $room->fill($request->only('name', 'description', 'area_id', 'room_type_id'));
        $this->roomRepository->save($room);
        $room->categories()->sync($request->categories);
        $room->attributes()->sync($request->attributes);
        $room->adjoiningRooms()->sync($request->adjoiningRooms);
        $room->roomAdmins()->sync($request->roomAdmins);
        $this->roomRepository->save($room);
        $this->history->createHistory($room->id, 'Room created');
        return $room;
    }

    /**
     * @param $roomId
     * @param $oldTemporary
     * @param $newTemporary
     * @param $oldStartDate
     * @param $newStartDate
     * @param $oldEndDate
     * @param $newEndDate
     * @return void
     */
    public function checkTemporaryChanges(
        $roomId,
        $oldTemporary,
        $newTemporary,
        $oldStartDate,
        $newStartDate,
        $oldEndDate,
        $newEndDate
    ): void {
        if ($oldTemporary && !$newTemporary) {
            $this->history->createHistory($roomId, 'Temporary time period deleted');
            return;
        }
        if ($newTemporary && !$oldTemporary) {
            $this->history->createHistory($roomId, 'Temporary time period added');
            return;
        }

        // add check if temporary not changed
        if ($oldTemporary && $newTemporary) {
            if ($oldStartDate !== $newStartDate || $oldEndDate !== $newEndDate) {
                $this->history->createHistory($roomId, 'Temporary time period changed');
                return;
            }
        }
    }

    /**
     * @param $roomId
     * @param $oldCategories
     * @param $newCategories
     * @return void
     */
    public function checkCategoryChanges($roomId, $oldCategories, $newCategories): void
    {
        $oldCategoryIds = [];
        $oldCategoryNames = [];
        $newCategoryIds = [];

        foreach ($oldCategories as $oldCategory) {
            $oldCategoryIds[$oldCategory->id] = $oldCategory->id;
            $oldCategoryNames[$oldCategory->id] = $oldCategory->name;
        }

        foreach ($newCategories as $newCategory) {
            $newCategoryIds[] = $newCategory->id;
            if (!in_array($newCategory->id, $oldCategoryIds)) {
                $this->history->createHistory(
                    $roomId,
                    'Added category',
                    [$newCategory->name]
                );
            }
        }

        foreach ($oldCategoryIds as $oldCategoryId) {
            if (!in_array($oldCategoryId, $newCategoryIds)) {
                $this->history->createHistory(
                    $roomId,
                    'Deleted category',
                    [$oldCategoryNames[$oldCategoryId]]
                );
            }
        }
    }

    /**
     * @param $roomId
     * @param $oldAttributes
     * @param $newAttributes
     * @return void
     */
    public function checkAttributeChanges($roomId, $oldAttributes, $newAttributes): void
    {
        $oldAttributeIds = [];
        $oldAttributeNames = [];
        $newAttributeIds = [];

        foreach ($oldAttributes as $oldAttribute) {
            $oldAttributeIds[] = $oldAttribute->id;
            $oldAttributeNames[$oldAttribute->id] = $oldAttribute->name;
        }

        foreach ($newAttributes as $newAttribute) {
            $newAttributeIds[] = $newAttribute->id;
            if (!in_array($newAttribute->id, $oldAttributeIds)) {
                $this->history->createHistory(
                    $roomId,
                    'Added attribute',
                    [$newAttribute->name]
                );
            }
        }

        foreach ($oldAttributeIds as $oldAttributeId) {
            if (!in_array($oldAttributeId, $newAttributeIds)) {
                $this->history->createHistory(
                    $roomId,
                    'Deleted attribute',
                    [$oldAttributeNames[$oldAttributeId]]
                );
            }
        }
    }

    /**
     * @param $roomId
     * @param $oldTitle
     * @param $newTitle
     * @return void
     */
    public function checkTitleChanges($roomId, $oldTitle, $newTitle): void
    {
        if ($oldTitle !== $newTitle) {
            $this->history->createHistory($roomId, 'Room name has been changed');
        }
    }

    /**
     * @param $roomId
     * @param $oldDescription
     * @param $newDescription
     * @return void
     */
    public function checkDescriptionChanges($roomId, $oldDescription, $newDescription): void
    {
        // check changes in room description
        if ($oldDescription !== $newDescription) {
            $this->history->createHistory($roomId, 'Description has been changed');
        }
    }

    /**
     * @param $roomId
     * @param $oldAdjoiningRooms
     * @param $newAdjoiningRooms
     * @return void
     */
    public function checkAdjoiningRoomChanges($roomId, $oldAdjoiningRooms, $newAdjoiningRooms): void
    {
        $newAdjoiningRoomIds = [];
        $oldAdjoiningRoomIds = [];
        $oldAdjoiningRoomName = [];

        foreach ($oldAdjoiningRooms as $oldAdjoiningRoom) {
            $oldAdjoiningRoomIds[] = $oldAdjoiningRoom->id;
            $oldAdjoiningRoomName[$oldAdjoiningRoom->id] = $oldAdjoiningRoom->name;
        }

        foreach ($newAdjoiningRooms as $newAdjoiningRoom) {
            $newAdjoiningRoomIds[] = $newAdjoiningRoom->id;
            if (!in_array($newAdjoiningRoom->id, $oldAdjoiningRoomIds)) {
                $this->history->createHistory(
                    $roomId,
                    'Adjoining room was added',
                    [$newAdjoiningRoom->name]
                );
            }
        }

        foreach ($oldAdjoiningRoomIds as $oldAdjoiningRoomId) {
            if (!in_array($oldAdjoiningRoomId, $newAdjoiningRoomIds)) {
                $this->history->createHistory(
                    $roomId,
                    'Adjoining room has been removed',
                    [$oldAdjoiningRoomName[$oldAdjoiningRoomId]]
                );
            }
        }
    }

    /**
     * @param Room $room
     * @param $roomAdminsBefore
     * @param $roomAdminsAfter
     * @return void
     */
    public function checkMemberChanges(Room $room, $roomAdminsBefore, $roomAdminsAfter): void
    {
        $roomAdminIdsBefore = [];
        $roomAdminIdsAfter = [];
        foreach ($roomAdminsBefore as $roomAdminBefore) {
            $roomAdminIdsBefore[] = $roomAdminBefore->id;
        }

        foreach ($roomAdminsAfter as $roomAdminAfter) {
            $roomAdminIdsAfter[] = $roomAdminAfter->id;
            if (!in_array($roomAdminAfter->id, $roomAdminIdsBefore)) {
                $user = User::find($roomAdminAfter->id);
                $notificationTitle = __(
                    'notifications.room.leader.add',
                    ['room' => $room->name],
                    $user->language
                );
                $broadcastMessage = [
                    'id' => rand(1, 1000000),
                    'type' => 'success',
                    'message' => $notificationTitle
                ];
                $this->notificationService->setTitle($notificationTitle);
                $this->notificationService->setIcon('green');
                $this->notificationService->setPriority(3);
                $this->notificationService->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_ROOM_CHANGED);
                $this->notificationService->setBroadcastMessage($broadcastMessage);
                $this->notificationService->setNotificationTo($user);
                $this->notificationService->createNotification();
                $this->history->createHistory(
                    $room->id,
                    'Added as room admin',
                    [$user->first_name]
                );
            }
        }

        // check if user remove as room admin
        foreach ($roomAdminIdsBefore as $roomAdminBefore) {
            if (!in_array($roomAdminBefore, $roomAdminIdsAfter)) {
                $user = User::find($roomAdminBefore);
                $notificationTitle = __(
                    'notifications.room.leader.remove',
                    ['room' => $room->name],
                    $user->language
                );
                $broadcastMessage = [
                    'id' => random_int(1, 1000000),
                    'type' => 'error',
                    'message' => $notificationTitle
                ];
                $this->notificationService->setTitle($notificationTitle);
                $this->notificationService->setIcon('red');
                $this->notificationService->setPriority(2);
                $this->notificationService->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_ROOM_CHANGED);
                $this->notificationService->setBroadcastMessage($broadcastMessage);
                $this->notificationService->setNotificationTo($user);
                $this->notificationService->createNotification();
                $this->history->createHistory(
                    $room->id,
                    'Removed as room admin',
                    [$user->first_name]
                );
            }
        }
    }

    public function deleteAllByArea(Area $area): void
    {
        $this->roomRepository->deleteByReference($area, 'rooms');
    }

    public function getAllWithoutTrashed(): EloquentCollection
    {
        return $this->roomRepository->allWithoutTrashed();
    }

    //@todo: fix phpcs error - complexity too high
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
    public function collectEventsForRoom(
        Room $room,
        CarbonPeriod $calendarPeriod,
        ?Project $project = null,
        $shiftPlan = false
    ): Collection {
        $user = Auth::user();
        if (!$shiftPlan) {
            $calendarFilter = $user->calendar_filter()->first();
        } else {
            $calendarFilter = $user->shift_calendar_filter()->first();
        }

        $isLoud = $calendarFilter->is_loud ?? false;
        $isNotLoud = $calendarFilter->is_not_loud ?? false;
        $hasAudience = $calendarFilter->has_audience ?? false;
        $hasNoAudience = $calendarFilter->has_no_audience ?? false;
        $showAdjoiningRooms = $calendarFilter->show_adjoining_rooms ?? false;
        $eventTypeIds = $calendarFilter->event_types ?? null;
        $roomIds = $calendarFilter->rooms ?? null;
        $areaIds = $calendarFilter->areas ?? null;
        $roomAttributeIds = $calendarFilter->room_attributes ?? null;
        $roomCategoryIds = $calendarFilter->room_categories ?? null;
        $eventsForRoom = $this->fillPeriodWithEmptyEventData($room, $calendarPeriod);
        $actualEvents = [];
        $room->events()
            ->where(function ($query) use ($calendarPeriod): void {
                $query->where(function ($q) use ($calendarPeriod): void {
                    // Events, die vor der Periode beginnen und nach der Periode enden
                    $q->where('start_time', '<', $calendarPeriod->start)
                        ->where('end_time', '>', $calendarPeriod->end);
                })->orWhere(function ($q) use ($calendarPeriod): void {
                    // Events, die innerhalb der Periode starten oder enden
                    $q->whereBetween('start_time', [$calendarPeriod->start, $calendarPeriod->end])
                        ->orWhereBetween('end_time', [$calendarPeriod->start, $calendarPeriod->end]);
                });
            })
            ->when($project, fn(Builder $builder) => $builder->where('project_id', $project->id))
            ->when($project, fn(EventBuilder $builder) => $builder->where('project_id', $project->id))
            ->when($room, fn(EventBuilder $builder) => $builder->where('room_id', $room->id))
            ->unless(
                empty($roomIds) && empty($areaIds) && empty($roomAttributeIds) && empty($roomCategoryIds),
                fn(EventBuilder $builder) => $builder->whereHas('room', fn(Builder $roomBuilder) => $roomBuilder
                    ->when($roomIds, fn(Builder $roomBuilder) => $roomBuilder->whereIn('rooms.id', $roomIds))
                    ->when($areaIds, fn(Builder $roomBuilder) => $roomBuilder->whereIn('area_id', $areaIds))
                    ->when($showAdjoiningRooms, fn(Builder $roomBuilder) => $roomBuilder->with('adjoining_rooms'))
                    ->when($roomAttributeIds, fn(Builder $roomBuilder) => $roomBuilder
                        ->whereHas('attributes', fn(Builder $roomAttributeBuilder) => $roomAttributeBuilder
                            ->whereIn('room_attributes.id', $roomAttributeIds)))
                    ->when($roomCategoryIds, fn(Builder $roomBuilder) => $roomBuilder
                        ->whereHas('categories', fn(Builder $roomCategoryBuilder) => $roomCategoryBuilder
                            ->whereIn('room_categories.id', $roomCategoryIds)))
                    ->without(['admins']))
            )
            ->unless(empty($eventTypeIds), function ($query) use ($eventTypeIds) {
                return $query->where(function ($query) use ($eventTypeIds): void {
                    $query->whereIn('event_type_id', $eventTypeIds)
                        ->orWhereHas('subEvents', function ($query) use ($eventTypeIds): void {
                            $query->whereIn('event_type_id', $eventTypeIds);
                        });
                });
            })
            ->unless(!$hasAudience, fn(EventBuilder $builder) => $builder->where('audience', true))
            ->unless(!$hasNoAudience, fn(EventBuilder $builder) => $builder->where('audience', false))
            ->unless(!$isLoud, fn(EventBuilder $builder) => $builder->where('is_loud', true))
            ->unless(!$isNotLoud, fn(EventBuilder $builder) => $builder->where('is_loud', false))
            ->each(function (Event $event) use (&$actualEvents, $calendarPeriod): void {
                // Erstelle einen Zeitraum für das Event, der innerhalb der gewünschten Periode liegt
                $eventStart = $event->start_time->isBefore($calendarPeriod->start) ?
                    $calendarPeriod->start :
                    $event->start_time;
                $eventEnd = $event->end_time->isAfter($calendarPeriod->end) ? $calendarPeriod->end : $event->end_time;
                $eventPeriod = CarbonPeriod::create($eventStart->startOfDay(), $eventEnd->endOfDay());

                foreach ($eventPeriod as $date) {
                    $dateKey = $date->format('d.m.Y');
                    $actualEvents[$dateKey][] = $event;
                }
            });
        foreach ($actualEvents as $key => $value) {
            $eventsForRoom[$key] = [
                'roomName' => $room->name,
                'events' => CalendarShowEventResource::collection($value)
            ];
        }
        return collect($eventsForRoom);
    }

    public function collectEventsForRooms(
        array|Collection $roomsWithEvents,
        CarbonPeriod $calendarPeriod,
        ?Project $project = null,
        $shiftPlan = false
    ): Collection {
        $roomEvents = collect();

        foreach ($roomsWithEvents as $room) {
            $roomEvents->add($this->collectEventsForRoom($room, $calendarPeriod, $project, $shiftPlan));
        }
        return $roomEvents;
    }

    /**
     * @return array<string, mixed>
     */
    private function fillPeriodWithEmptyEventData(
        Room $room,
        CarbonPeriod $calendarPeriod
    ): array {
        $eventsForRoom = [];
        /** @var Collection $eventsForRoom */
        foreach ($calendarPeriod as $date) {
            $eventsForRoom[$date->format('d.m.Y')] = [
                'roomName' => $room->name,
                'events' => CalendarShowEventResource::collection([])
            ];
        }
        return $eventsForRoom;
    }
}
