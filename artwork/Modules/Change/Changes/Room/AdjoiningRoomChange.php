<?php

namespace Artwork\Modules\Change\Changes\Room;

use Artwork\Modules\Change\Interfaces\RoomChange;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\User\Models\User;

readonly class AdjoiningRoomChange implements RoomChange
{
    public function __construct(
        private ChangeService $changeService
    ) {
    }

    public function change(Room $room, Room $oldRoom): void
    {
        $newAdjoiningRoomIds = [];
        $oldAdjoiningRoomIds = [];
        $oldAdjoiningRoomName = [];

        foreach ($oldRoom->adjoining_rooms as $oldAdjoiningRoom) {
            $oldAdjoiningRoomIds[] = $oldAdjoiningRoom->id;
            $oldAdjoiningRoomName[$oldAdjoiningRoom->id] = $oldAdjoiningRoom->name;
        }

        foreach ($room->adjoining_rooms as $newAdjoiningRoom) {
            $newAdjoiningRoomIds[] = $newAdjoiningRoom->id;
            if (!in_array($newAdjoiningRoom->id, $oldAdjoiningRoomIds, true)) {
                $this->changeService->saveFromBuilder(
                    $this->changeService
                        ->createBuilder()
                        ->setModelClass(Room::class)
                        ->setModelId($room->id)
                        ->setTranslationKey('Adjoining room was added')
                        ->setTranslationKeyPlaceholderValues([$newAdjoiningRoom->name])
                );
            }
        }

        foreach ($oldAdjoiningRoomIds as $oldAdjoiningRoomId) {
            if (!in_array($oldAdjoiningRoomId, $newAdjoiningRoomIds, true)) {
                $this->changeService->saveFromBuilder(
                    $this->changeService
                        ->createBuilder()
                        ->setModelClass(Room::class)
                        ->setModelId($room->id)
                        ->setTranslationKey('Adjoining room has been removed')
                        ->setTranslationKeyPlaceholderValues([$oldAdjoiningRoomName[$oldAdjoiningRoomId]])
                );
            }
        }
    }
}
