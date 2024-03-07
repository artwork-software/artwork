<?php

namespace Artwork\Modules\BudgetManagementCostUnit\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\BudgetManagementCostUnit\Models\BudgetManagementCostUnit;
use Illuminate\Database\Eloquent\Collection;

class BudgetManagementCostUnitRepository extends BaseRepository
{
    public function getAll(): Collection
    {
        return BudgetManagementCostUnit::all();
    }
}
