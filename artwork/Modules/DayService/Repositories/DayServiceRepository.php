<?php

namespace Artwork\Modules\DayService\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\DayService\Models\DayService;
use Illuminate\Database\Eloquent\Collection;

readonly class DayServiceRepository extends BaseRepository
{
    public function getAll(): Collection
    {
        return DayService::all();
    }

}
