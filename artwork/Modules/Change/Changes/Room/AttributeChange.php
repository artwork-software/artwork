<?php

namespace Artwork\Modules\Change\Changes\Room;

use Artwork\Modules\Change\Interfaces\RoomChange;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\User\Models\User;

readonly class AttributeChange implements RoomChange
{
    public function __construct(
        private ChangeService $changeService
    ) {
    }

    public function change(Room $room, Room $oldRoom): void
    {
        $oldAttributeIds = [];
        $oldAttributeNames = [];
        $newAttributeIds = [];

        foreach ($oldRoom->attributes as $oldAttribute) {
            $oldAttributeIds[] = $oldAttribute->id;
            $oldAttributeNames[$oldAttribute->id] = $oldAttribute->name;
        }

        foreach ($room->attributes as $newAttribute) {
            $newAttributeIds[] = $newAttribute->id;
            if (!in_array($newAttribute->id, $oldAttributeIds, true)) {
                $this->changeService->saveFromBuilder(
                    $this->changeService
                        ->createBuilder()
                        ->setModelClass(Room::class)
                        ->setModelId($room->id)
                        ->setTranslationKey('Added attribute')
                        ->setTranslationKeyPlaceholderValues([$newAttribute->name])
                );
            }
        }

        foreach ($oldAttributeIds as $oldAttributeId) {
            if (!in_array($oldAttributeId, $newAttributeIds, true)) {
                $this->changeService->saveFromBuilder(
                    $this->changeService
                        ->createBuilder()
                        ->setModelClass(Room::class)
                        ->setModelId($room->id)
                        ->setTranslationKey('Deleted attribute')
                        ->setTranslationKeyPlaceholderValues([$oldAttributeNames[$oldAttributeId]])
                );
            }
        }
    }
}
