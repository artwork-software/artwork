<?php

namespace Artwork\Modules\RoomAttribute\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\RoomAttribute\Models\RoomAttribute;
use Illuminate\Database\Eloquent\Collection;

readonly class RoomAttributeRepository extends BaseRepository
{
    public function getAll(): Collection
    {
        return RoomAttribute::all();
    }
}
