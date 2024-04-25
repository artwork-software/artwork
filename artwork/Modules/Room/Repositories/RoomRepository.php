<?php

namespace Artwork\Modules\Room\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Room\Models\Room;
use Illuminate\Database\Eloquent\Collection;

readonly class RoomRepository extends BaseRepository
{
    public function allWithoutTrashed(): Collection
    {
        return Room::withoutTrashed()->get();
    }
}
