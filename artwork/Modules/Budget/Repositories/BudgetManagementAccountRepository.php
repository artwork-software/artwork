<?php

namespace Artwork\Modules\Budget\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Budget\Models\BudgetManagementAccount;
use Illuminate\Database\Eloquent\Collection;

class BudgetManagementAccountRepository extends BaseRepository
{
    public const SEARCH_RESULT_LIMIT = 50;

    public function getAllOrderedByIsAccountForRevenue(): Collection
    {
        return BudgetManagementAccount::query()->orderBy('is_account_for_revenue')->get();
    }

    public function getAllTrashed(): Collection
    {
        return BudgetManagementAccount::onlyTrashed()->get();
    }

    public function getByAccountNumberOrTitleAndIsAccountForRevenue(
        string $search,
        bool $isAccountForRevenue
    ): Collection {
        return BudgetManagementAccount::query()
            ->byAccountNumberOrTitle($search)
            ->isAccountForRevenue($isAccountForRevenue)
            ->orderBy('account_number')
            // Trefferlimit: das Frontend zeigt bei 50 Treffern "Suche verfeinern".
            ->limit(self::SEARCH_RESULT_LIMIT)
            ->get();
    }
}
