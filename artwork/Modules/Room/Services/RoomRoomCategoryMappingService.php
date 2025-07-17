<?php

namespace Artwork\Modules\Room\Services;

use Artwork\Modules\Room\Repositories\RoomRoomCategoryMappingRepository;

readonly class RoomRoomCategoryMappingService
{
    public function __construct(private RoomRoomCategoryMappingRepository $roomRoomCategoryMappingRepository)
    {
    }
}
