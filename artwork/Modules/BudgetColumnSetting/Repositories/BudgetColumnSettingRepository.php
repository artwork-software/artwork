<?php

namespace Artwork\Modules\BudgetColumnSetting\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\BudgetColumnSetting\Models\BudgetColumnSetting;
use Illuminate\Database\Eloquent\Collection;

readonly class BudgetColumnSettingRepository extends BaseRepository
{
    public function getAll(): Collection
    {
        return BudgetColumnSetting::all();
    }

    public function findByColumnPosition(int $position): BudgetColumnSetting|null
    {
        return BudgetColumnSetting::byColumnPosition($position)->first();
    }
}
