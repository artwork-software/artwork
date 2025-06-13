<?php

namespace Artwork\Modules\Budget\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Budget\Models\BudgetManagementCostUnit;
use Illuminate\Database\Eloquent\Collection;

class BudgetManagementCostUnitRepository extends BaseRepository
{
    public function getAll(): Collection
    {
        return BudgetManagementCostUnit::all();
    }

    public function getAllTrashed(): Collection
    {
        return BudgetManagementCostUnit::onlyTrashed()->get();
    }

    public function getByCostUnitNumberOrTitle(string $search): Collection
    {
        return BudgetManagementCostUnit::byCostUnitNumberOrTitle($search)->get();
    }
}
