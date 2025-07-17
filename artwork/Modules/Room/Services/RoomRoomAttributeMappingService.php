<?php

namespace Artwork\Modules\Room\Services;

use Artwork\Modules\Room\Repositories\RoomRoomAttributeMappingRepository;

readonly class RoomRoomAttributeMappingService
{
    public function __construct(private RoomRoomAttributeMappingRepository $roomRoomAttributeMappingRepository)
    {
    }
}
