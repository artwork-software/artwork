<?php

namespace App\Support\Services;

use App\Enums\NotificationConstEnum;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class RoomService
{
    protected ?NotificationService $notificationService = null;
    protected ?\stdClass $notificationData = null;
    protected NewHistoryService $history;

    public function __construct()
    {
        $this->notificationService = new NotificationService();
        $this->notificationData = new \stdClass();
        $this->notificationData->room = new \stdClass();
        $this->notificationData->type = NotificationConstEnum::NOTIFICATION_ROOM_CHANGED;
        $this->history = new NewHistoryService('App\Models\Room');
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
    public function checkTemporaryChanges($roomId, $oldTemporary, $newTemporary, $oldStartDate, $newStartDate, $oldEndDate, $newEndDate): void
    {
        if($oldTemporary && !$newTemporary){
            $this->history->createHistory($roomId, 'Temporärer Zeitraum gelöscht');
        }
        if($newTemporary && !$oldTemporary){
            $this->history->createHistory($roomId, 'Temporärer Zeitraum hinzugefügt');
        }

        if($oldStartDate !== $newStartDate || $oldEndDate !== $newEndDate){
            $this->history->createHistory($roomId, 'Temporärer Zeitraum geändert');
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

        foreach ($oldCategories as $oldCategory){
            $oldCategoryIds[$oldCategory->id] = $oldCategory->id;
            $oldCategoryNames[$oldCategory->id] = $oldCategory->name;
        }

        foreach ($newCategories as $newCategory){
            $newCategoryIds[] = $newCategory->id;
            if(!in_array($newCategory->id, $oldCategoryIds)){
                $this->history->createHistory($roomId, 'Kategorie ' . $newCategory->name . ' wurde hinzugefügt');
            }
        }

        foreach ($oldCategoryIds as $oldCategoryId){
            if(!in_array($oldCategoryId, $newCategoryIds)){
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

        foreach ($oldAttributes as $oldAttribute){
            $oldAttributeIds[] = $oldAttribute->id;
            $oldAttributeNames[$oldAttribute->id] = $oldAttribute->name;
        }

        foreach ($newAttributes as $newAttribute){
            $newAttributeIds[] = $newAttribute->id;
            if(!in_array($newAttribute->id, $oldAttributeIds)){
                $this->history->createHistory($roomId, 'Attribut ' . $newAttribute->name . ' wurde hinzugefügt');
            }
        }

        foreach ($oldAttributeIds as $oldAttributeId){
            if(!in_array($oldAttributeId, $newAttributeIds)){
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
        if($oldTitle !== $newTitle){
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
        if($oldDescription !== $newDescription){
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

        foreach ($oldAdjoiningRooms as $oldAdjoiningRoom){
            $oldAdjoiningRoomIds[] = $oldAdjoiningRoom->id;
            $oldAdjoiningRoomName[$oldAdjoiningRoom->id] = $oldAdjoiningRoom->name;
        }

        foreach ($newAdjoiningRooms as $newAdjoiningRoom){
            $newAdjoiningRoomIds[] = $newAdjoiningRoom->id;
            if(!in_array($newAdjoiningRoom->id, $oldAdjoiningRoomIds)){
                $this->history->createHistory($roomId, 'Nebenraum ' . $newAdjoiningRoom->name . ' wurde hinzugefügt');
            }
        }

        foreach ($oldAdjoiningRoomIds as $oldAdjoiningRoomId){
            if(!in_array($oldAdjoiningRoomId, $newAdjoiningRoomIds)){
                $this->history->createHistory($roomId, 'Nebenraum ' . $oldAdjoiningRoomName[$oldAdjoiningRoomId]. ' wurde entfernt');
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
        foreach ($roomAdminsBefore as $roomAdminBefore){
            $roomAdminIdsBefore[] = $roomAdminBefore->id;
        }

        foreach ($roomAdminsAfter as $roomAdminAfter){
            $roomAdminIdsAfter[] = $roomAdminAfter->id;
            // if added a new room admin, send notification to this user
            if(!in_array($roomAdminAfter->id, $roomAdminIdsBefore)){
                $user = User::find($roomAdminAfter->id);
                $this->notificationData->title = 'Du wurdest zum Raumadmin von "' . $room->name . '" ernannt';
                $this->notificationData->room = $room;
                $this->notificationData->created_by = User::where('id', Auth::id())->first();
                $broadcastMessage = [
                    'id' => rand(1, 1000000),
                    'type' => 'success',
                    'message' => $this->notificationData->title
                ];
                $this->notificationService->create($user, $this->notificationData, $broadcastMessage);
                $this->history->createHistory($room->id, $user->first_name . ' als Raumadmin hinzugefügt');
            }
        }

        // check if user remove as room admin
        foreach ($roomAdminIdsBefore as $roomAdminBefore){
            if(!in_array($roomAdminBefore, $roomAdminIdsAfter)){
                $user = User::find($roomAdminBefore);
                $this->notificationData->title = 'Du wurdest als Raumadmin von "' . $room->name . '" gelöscht';
                $this->notificationData->room = $room;
                $this->notificationData->created_by = User::where('id', Auth::id())->first();
                $broadcastMessage = [
                    'id' => rand(1, 1000000),
                    'type' => 'error',
                    'message' => $this->notificationData->title
                ];
                $this->notificationService->create($user, $this->notificationData, $broadcastMessage);
                $this->history->createHistory($room->id, $user->first_name . ' als Raumadmin entfernt');
            }
        }
    }

}
