<?php

namespace Artwork\Modules\Room\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Room\Models\Room;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

readonly class RoomRepository extends BaseRepository
{
    public function allWithoutTrashed($with = []): Collection
    {
        $query = Room::withoutTrashed();

        if (count($with) > 0) {
            $query->with($with);
        }

        return $query->get();
    }

    public function findOrFail(int $id): Room
    {
        return Room::findOrFail($id);
    }

    public function getFilteredRoomsBy(
        ?array $roomIds,
        ?array $roomAttributeIds,
        ?array $areaIds,
        ?array $roomCategoryIds,
        ?bool $adjoiningNotLoud,
        ?bool $adjoiningNoAudience,
        Carbon $startDate,
        Carbon $endDate
    ): Collection {
        return Room::query()
            ->unlessRoomIds($roomIds)
            ->unlessRoomAttributeIds($roomAttributeIds)
            ->unlessAreaIds($areaIds)
            ->unlessRoomCategoryIds($roomCategoryIds)
            ->whenFilterAdjoiningWithStartAndEndDate(
                $adjoiningNotLoud,
                $adjoiningNoAudience,
                $startDate,
                $endDate
            )
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
