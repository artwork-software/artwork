<?php

namespace Artwork\Modules\Room\Services;

use App\Enums\NotificationConstEnum;
use App\Http\Resources\CalendarShowEventResource;
use App\Models\Event;
use App\Models\User;
use App\Support\Services\NewHistoryService;
use App\Support\Services\NotificationService;
use Artwork\Modules\Area\Models\Area;
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
                fn(\Illuminate\Database\Eloquent\Builder $builder) => $builder->whereIn('id', $roomIds)
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
            );
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
        $this->history->createHistory($room->id, 'Raum erstellt');
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
            $this->history->createHistory($roomId, 'Temporärer Zeitraum gelöscht');
            return;
        }
        if ($newTemporary && !$oldTemporary) {
            $this->history->createHistory($roomId, 'Temporärer Zeitraum hinzugefügt');
            return;
        }

        // add check if temporary not changed
        if ($oldTemporary && $newTemporary) {
            if ($oldStartDate !== $newStartDate || $oldEndDate !== $newEndDate) {
                $this->history->createHistory($roomId, 'Temporärer Zeitraum geändert');
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
                $this->history->createHistory($roomId, 'Kategorie ' . $newCategory->name . ' wurde hinzugefügt');
            }
        }

        foreach ($oldCategoryIds as $oldCategoryId) {
            if (!in_array($oldCategoryId, $newCategoryIds)) {
                $this->history->createHistory($roomId, 'Kategorie ' . $oldCategoryNames[$oldCategoryId] . ' wurde entfernt');
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
                $this->history->createHistory($roomId, 'Attribut ' . $newAttribute->name . ' wurde hinzugefügt');
            }
        }

        foreach ($oldAttributeIds as $oldAttributeId) {
            if (!in_array($oldAttributeId, $newAttributeIds)) {
                $this->history->createHistory($roomId, 'Attribut ' . $oldAttributeNames[$oldAttributeId] . ' wurde entfernt');
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
            $this->history->createHistory($roomId, 'Raumname wurde geändert');
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
            $this->history->createHistory($roomId, 'Beschreibung wurde geändert');
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
                $this->history->createHistory($roomId, 'Nebenraum ' . $newAdjoiningRoom->name . ' wurde hinzugefügt');
            }
        }

        foreach ($oldAdjoiningRoomIds as $oldAdjoiningRoomId) {
            if (!in_array($oldAdjoiningRoomId, $newAdjoiningRoomIds)) {
                $this->history->createHistory($roomId, 'Nebenraum ' . $oldAdjoiningRoomName[$oldAdjoiningRoomId] . ' wurde entfernt');
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
            // if added a new room admin, send notification to this user
            if (!in_array($roomAdminAfter->id, $roomAdminIdsBefore)) {
                $notificationTitle = 'Du wurdest zum Raumadmin von "' . $room->name . '" ernannt';
                $user = User::find($roomAdminAfter->id);
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
                $this->history->createHistory($room->id, $user->first_name . ' als Raumadmin hinzugefügt');
            }
        }

        // check if user remove as room admin
        foreach ($roomAdminIdsBefore as $roomAdminBefore) {
            if (!in_array($roomAdminBefore, $roomAdminIdsAfter)) {
                $user = User::find($roomAdminBefore);
                $notificationTitle = 'Du wurdest als Raumadmin von "' . $room->name . '" gelöscht';
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
                $this->history->createHistory($room->id, $user->first_name . ' als Raumadmin entfernt');
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

    public function collectEventsForRoom(Room $room, CarbonPeriod $calendarPeriod, ?Project $project = null): Collection
    {
        $eventsForRoom = $this->fillPeriodWithEmptyEventData($room, $calendarPeriod);
        $actualEvents = [];
        $room->events()->where('start_time', '>=', $calendarPeriod->start)
            ->where('end_time', '<=', $calendarPeriod->end)
            ->when($project, fn(Builder $builder) => $builder->where('project_id', $project->id))
            ->each(function (Event $event) use (&$actualEvents): void {
                $dateKey = $event->start_time->format('d.m.');
                $actualEvents[$dateKey][] = $event;
            });
        foreach ($actualEvents as $key => $value) {
            $eventsForRoom[$key] = ['roomName' => $room->name, 'events' => CalendarShowEventResource::collection($value)];
        }
        //dd(collect($eventsForRoom));
        return collect($eventsForRoom);
    }

    public function collectEventsForRooms(array|Collection $roomsWithEvents, CarbonPeriod $calendarPeriod, ?Project $project = null): Collection
    {
        $roomEvents = collect();

        foreach ($roomsWithEvents as $room) {
            $roomEvents->add($this->collectEventsForRoom($room, $calendarPeriod, $project));
        }
        return $roomEvents;
    }

    private function fillPeriodWithEmptyEventData(Room $room, CarbonPeriod $calendarPeriod): array
    {
        $eventsForRoom = [];
        /** @var Collection $eventsForRoom */
        foreach ($calendarPeriod as $date) {
            $eventsForRoom[$date->format('d.m.')] = ['roomName' => $room->name, 'events' => CalendarShowEventResource::collection([])];
        }
        return $eventsForRoom;
    }
}
