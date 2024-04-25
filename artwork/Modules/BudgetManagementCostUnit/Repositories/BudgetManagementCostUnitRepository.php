<?php

namespace Artwork\Modules\BudgetManagementCostUnit\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\BudgetManagementCostUnit\Models\BudgetManagementCostUnit;
use Illuminate\Database\Eloquent\Collection;

readonly class BudgetManagementCostUnitRepository extends BaseRepository
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
