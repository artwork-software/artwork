<?php

namespace Artwork\Modules\Change\Changes\Room;

use Artwork\Modules\Change\Interfaces\RoomChange;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\User\Models\User;

readonly class TemporaryChange implements RoomChange
{
    public function __construct(
        private ChangeService $changeService
    ) {
    }

    public function change(Room $room, Room $oldRoom): void
    {
        if ($oldRoom->temporary && !$room->temporary) {
            $this->temporaryTimeDeleted($room);
            return;
        }

        if ($room->temporary && !$oldRoom->temporary) {
            $this->temporaryTimeDeleted($room);
            return;
        }

        // add check if temporary not changed
        if ($oldRoom->temporary && $room->temporary) {
            $this->temporaryTimePeriodChanged($room, $oldRoom);
        }
    }

    private function temporaryTimePeriodChanged(Room $room, Room $oldRoom): void
    {
        if ($oldRoom->start_date !== $room->start_date || $oldRoom->end_date !== $room->end_date) {
            $this->changeService->saveFromBuilder(
                $this->changeService
                    ->createBuilder()
                    ->setModelClass(Room::class)
                    ->setModelId($room->id)
                    ->setTranslationKey('Temporary time period changed')
            );
        }
    }

    private function temporaryTimeDeleted(Room $room): void
    {
        $this->changeService->saveFromBuilder(
            $this->changeService
                ->createBuilder()
                ->setModelClass(Room::class)
                ->setModelId($room->id)
                ->setTranslationKey('Temporary time period deleted')
        );
    }

    private function temporaryTimeAdded(Room $room): void
    {
        $this->changeService->saveFromBuilder(
            $this->changeService
                ->createBuilder()
                ->setModelClass(Room::class)
                ->setModelId($room->id)
                ->setTranslationKey('Temporary time period added')
        );
    }
}
