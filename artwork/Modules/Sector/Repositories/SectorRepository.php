<?php

namespace Artwork\Modules\Sector\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Sector\Models\Sector;

readonly class SectorRepository extends BaseRepository
{
    public function getAll(): \Illuminate\Database\Eloquent\Collection
    {
        return Sector::all();
    }
}
