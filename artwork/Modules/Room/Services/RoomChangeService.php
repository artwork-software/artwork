<?php

namespace Artwork\Modules\Room\Services;

use Artwork\Modules\Change\Changes\Room\RoomChangeFactory;
use Artwork\Modules\Room\Models\Room;

readonly class RoomChangeService
{
    public function __construct(
        private RoomChangeFactory $roomChangeFactory,
    ) {
    }

    public function applyChanges(
        Room $room,
        Room $roomReplicate
    ): void {
        foreach ($this->roomChangeFactory->getRoomChangesAll() as $roomChange) {
            $roomChange->change($room, $roomReplicate);
        }
    }
}
