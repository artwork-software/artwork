<?php

namespace Artwork\Modules\Room\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Room\Models\RoomCategory;
use Illuminate\Database\Eloquent\Collection;

class RoomCategoryRepository extends BaseRepository
{
    public function getAll(): Collection
    {
        return RoomCategory::all();
    }
}
