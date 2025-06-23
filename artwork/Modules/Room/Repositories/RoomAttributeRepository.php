<?php

namespace Artwork\Modules\Room\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Room\Models\RoomAttribute;
use Illuminate\Database\Eloquent\Collection;

class RoomAttributeRepository extends BaseRepository
{
    public function getAll(): Collection
    {
        return RoomAttribute::all();
    }
}
