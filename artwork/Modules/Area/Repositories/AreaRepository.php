<?php

namespace Artwork\Modules\Area\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Area\Models\Area;
use Illuminate\Database\Eloquent\Collection;

readonly class AreaRepository extends BaseRepository
{
    public function getAll(): Collection
    {
        return Area::all();
    }
}
