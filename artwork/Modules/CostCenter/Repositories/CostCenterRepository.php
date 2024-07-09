<?php

namespace Artwork\Modules\CostCenter\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\CostCenter\Models\CostCenter;

class CostCenterRepository extends BaseRepository
{

    public function findCostCenterByName(string $name): CostCenter|null
    {
        return CostCenter::where('name', $name)->first();
    }
}
