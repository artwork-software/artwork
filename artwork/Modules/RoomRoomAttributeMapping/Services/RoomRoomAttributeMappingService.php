<?php

namespace Artwork\Modules\RoomRoomAttributeMapping\Services;

use Artwork\Modules\RoomRoomAttributeMapping\Repositories\RoomRoomAttributeMappingRepository;

readonly class RoomRoomAttributeMappingService
{
    public function __construct(private RoomRoomAttributeMappingRepository $roomRoomAttributeMappingRepository)
    {
    }
}
