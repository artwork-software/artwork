<?php

namespace Artwork\Modules\Change\Interfaces;

use Artwork\Modules\Room\Models\Room;

interface RoomChange
{
    public function change(Room $room, Room $oldRoom): void;
}
