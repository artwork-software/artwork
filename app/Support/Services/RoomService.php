<?php

namespace App\Support\Services;

use App\Enums\NotificationConstEnum;
use App\Models\User;
use Artwork\Modules\Room\Models\Room;

class RoomService
{
    protected ?\stdClass $notificationData = null;

    protected NewHistoryService $history;

    public function __construct(protected readonly NotificationService $notificationService)
    {
        $this->history = new NewHistoryService(Room::class);
    }

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
            $this->history->createHistory($roomId, 'Temporary period deleted');
        }
        if ($newTemporary && !$oldTemporary) {
            $this->history->createHistory($roomId, 'Temporary period added');
        }

        if ($oldStartDate !== $newStartDate || $oldEndDate !== $newEndDate) {
            $this->history->createHistory($roomId, 'Temporary period changed');
        }
    }

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
                $this->history
                    ->createHistory(
                        $roomId,
                        'Deleted attribute',
                        [$oldAttributeNames[$oldAttributeId]]
                    );
            }
        }
    }

    public function checkTitleChanges($roomId, $oldTitle, $newTitle): void
    {
        if ($oldTitle !== $newTitle) {
            $this->history->createHistory($roomId, 'Room name has been changed');
        }
    }

    public function checkDescriptionChanges($roomId, $oldDescription, $newDescription): void
    {
        if ($oldDescription !== $newDescription) {
            $this->history->createHistory($roomId, 'Description has been changed');
        }
    }

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
                $notificationTitle = 'Du wurdest als Raumadmin von "' . $room->name . '" gelöscht';
                $broadcastMessage = [
                    'id' => rand(1, 1000000),
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
}
