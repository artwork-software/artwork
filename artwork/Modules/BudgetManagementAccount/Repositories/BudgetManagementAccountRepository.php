<?php

namespace Artwork\Modules\BudgetManagementAccount\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\BudgetManagementAccount\Models\BudgetManagementAccount;
use Illuminate\Database\Eloquent\Collection;

readonly class BudgetManagementAccountRepository extends BaseRepository
{
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
            ->get();
    }
}
