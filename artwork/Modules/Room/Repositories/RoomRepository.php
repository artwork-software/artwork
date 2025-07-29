<?php

namespace Artwork\Modules\Room\Repositories;

use Artwork\Core\Database\Models\CanSubstituteBaseModel;
use Artwork\Core\Database\Models\Model;
use Artwork\Core\Database\Models\Pivot;
use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Room\Models\Room;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection as SupportCollection;

class RoomRepository extends BaseRepository
{
    public function __construct(private readonly Room $room)
    {
    }

    public function getNewModelInstance(): Model|Pivot|DatabaseNotification|CanSubstituteBaseModel
    {
        return $this->room->newInstance();
    }

    public function getNewModelQuery(): BaseBuilder|Builder
    {
        return $this->room->newModelQuery();
    }

    public function allWithoutTrashed($with = [], $without = []): Collection
    {
        $query = Room::withoutTrashed();

        if (count($with) > 0) {
            $query->with($with);
        }

        if (count($without) > 0) {
            $query->without($without);
        }

        return $query->get();
    }

    public function getFilteredRoomsBy(
        ?array $roomIds,
        ?array $roomAttributeIds,
        ?array $areaIds,
        ?array $roomCategoryIds
    ): Collection {
        return Room::query()
            ->unlessRoomIds($roomIds)
            ->unlessRoomAttributeIds($roomAttributeIds)
            ->unlessAreaIds($areaIds)
            ->unlessRoomCategoryIds($roomCategoryIds)
            ->orderBy('order')
            ->get();
    }


    public function getUserWhereIsAdmin(int|Room $room, int $userId): Collection
    {
        if (!$room instanceof Room) {
            $room = $this->findOrFail($room);
        }

        return $room
            ->users()
            ->wherePivot('is_admin', true)
            ->wherePivot('user_id', '=', $userId)
            ->get();
    }

    public function getRoomCategoryIds(int|Room $room): SupportCollection
    {
        if (!$room instanceof Room) {
            $room = $this->findOrFail($room);
        }

        return $room->categories()->pluck('room_category_id');
    }

    public function getRoomAttributeIds(int|Room $room): SupportCollection
    {
        if (!$room instanceof Room) {
            $room = $this->findOrFail($room);
        }

        return $room->attributes()->pluck('room_attribute_id');
    }

    public function getAdjoiningRoomIds(int|Room $room): SupportCollection
    {
        if (!$room instanceof Room) {
            $room = $this->findOrFail($room);
        }

        return $room->adjoining_rooms()->pluck('adjoining_room_id');
    }

    public function getRoomsNotIdIn(array $ids): Collection
    {
        return Room::query()->notIdIn($ids)->get();
    }

    public function getFallbackRoom(): ?Room
    {
        return Room::query()->where('fallback_room', true)->first();
    }

    public function findByName(string $name): ?Room
    {
        return Room::query()->where('name', $name)->first();
    }
}
