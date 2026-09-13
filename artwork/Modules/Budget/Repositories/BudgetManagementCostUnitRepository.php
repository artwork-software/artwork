<?php

namespace Artwork\Modules\Budget\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Budget\Models\BudgetManagementCostUnit;
use Illuminate\Database\Eloquent\Collection;

class BudgetManagementCostUnitRepository extends BaseRepository
{
    public const SEARCH_RESULT_LIMIT = 50;

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
        return BudgetManagementCostUnit::byCostUnitNumberOrTitle($search)
            ->orderBy('cost_unit_number')
            // Trefferlimit: das Frontend zeigt bei 50 Treffern "Suche verfeinern".
            ->limit(self::SEARCH_RESULT_LIMIT)
            ->get();
    }
}
