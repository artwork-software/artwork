<?php

namespace Artwork\Modules\RoomCategory\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\RoomCategory\Models\RoomCategory;
use Illuminate\Database\Eloquent\Collection;

readonly class RoomCategoryRepository extends BaseRepository
{
    public function getAll(): Collection
    {
        return RoomCategory::all();
    }
}
