<?php

namespace Artwork\Modules\RoomRoomCategoryMapping\Services;

use Artwork\Modules\RoomRoomCategoryMapping\Repositories\RoomRoomCategoryMappingRepository;

readonly class RoomRoomCategoryMappingService
{
    public function __construct(private RoomRoomCategoryMappingRepository $roomRoomCategoryMappingRepository)
    {
    }
}
