<?php

namespace Artwork\Modules\Room\Services;

use Artwork\Modules\Category\Http\Resources\CategoryIndexResource;
use Artwork\Modules\Room\DTOs\ShowDto;
use Artwork\Modules\Room\Http\Resources\AdjoiningRoomIndexResource;
use Artwork\Modules\Room\Http\Resources\AttributeIndexResource;
use Artwork\Modules\Room\Http\Resources\RoomCalendarResource;
use Artwork\Modules\Room\Http\Resources\RoomIndexWithoutEventsResource;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Room\Repositories\RoomRepository;
use Artwork\Modules\Room\Repositories\RoomAttributeRepository;
use Artwork\Modules\Room\Repositories\RoomCategoryRepository;
use Artwork\Modules\User\Models\User;

readonly class RoomFrontendModelService
{

    public function __construct(
        private RoomCategoryRepository $roomCategoryRepository,
        private RoomAttributeRepository $roomAttributeRepository,
        private RoomRepository $roomRepository,
    ) {
    }
    public function createShowDto(
        Room $room,
        User $user
    ): ShowDto {

        return ShowDto::newInstance()
            ->setAvailableCategories($this->roomCategoryRepository->getAll())
            ->setAvailableAttributes($this->roomAttributeRepository->getAll())
            ->setIsRoomAdmin($this->roomRepository->getUserWhereIsAdmin($room, $user->id)->count() > 0)
            ->setAvailableRooms($this->roomRepository->getRoomsNotIdIn([$room->id]))
            ->setRoomCategoryIds($this->roomRepository->getRoomCategoryIds($room))
            ->setRoomAttributeIds($this->roomRepository->getRoomAttributeIds($room))
            ->setAdjoiningRoomIds($this->roomRepository->getAdjoiningRoomIds($room))
            ->setRoom(RoomCalendarResource::make($room))
            ->setRoomCategories(CategoryIndexResource::collection($room->categories)->resolve())
            ->setRoomAttributes(AttributeIndexResource::collection($room->attributes)->resolve())
            ->setAdjoiningRooms(AdjoiningRoomIndexResource::collection($room->adjoining_rooms)->resolve())
            ->setRooms(RoomIndexWithoutEventsResource::collection($this
                ->roomRepository->allWithoutTrashed())->resolve());
    }
}
