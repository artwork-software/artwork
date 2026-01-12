<?php

namespace Artwork\Modules\DayService\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\DayService\Models\DayService;
use Illuminate\Database\Eloquent\Collection;

class DayServiceRepository extends BaseRepository
{
    public function getAll(): Collection
    {
        return DayService::query()
            ->select(['id', 'name', 'icon', 'hex_color'])
            ->get();
    }
}
