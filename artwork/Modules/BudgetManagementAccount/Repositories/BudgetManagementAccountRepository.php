<?php

namespace Artwork\Modules\BudgetManagementAccount\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\BudgetManagementAccount\Models\BudgetManagementAccount;
use Illuminate\Database\Eloquent\Collection;

class BudgetManagementAccountRepository extends BaseRepository
{
    public function getAll(): Collection
    {
        return BudgetManagementAccount::all();
    }
}
