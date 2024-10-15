<?php

namespace Artwork\Modules\Change\Changes\Room;

use Artwork\Modules\Change\Interfaces\RoomChange;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\User\Models\User;

readonly class NameChange implements RoomChange
{
    public function __construct(
        private ChangeService $changeService
    ) {
    }

    public function change(Room $room, Room $oldRoom): void
    {
        if ($oldRoom->name !== $room->name) {
            $this->changeService->saveFromBuilder(
                $this->changeService
                    ->createBuilder()
                    ->setModelClass(Room::class)
                    ->setModelId($room->id)
                    ->setTranslationKey('Room name has been changed')
            );
        }
    }
}
