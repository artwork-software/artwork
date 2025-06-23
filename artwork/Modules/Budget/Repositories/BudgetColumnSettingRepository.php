<?php

namespace Artwork\Modules\Budget\Repositories;

use Artwork\Core\Database\Models\CanSubstituteBaseModel;
use Artwork\Core\Database\Models\Model;
use Artwork\Core\Database\Models\Pivot;
use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Budget\Models\BudgetColumnSetting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Notifications\DatabaseNotification;

class BudgetColumnSettingRepository extends BaseRepository
{
    public function __construct(private readonly BudgetColumnSetting $budgetColumnSetting)
    {
    }

    public function getNewModelInstance(): Model|Pivot|DatabaseNotification|CanSubstituteBaseModel
    {
        return $this->budgetColumnSetting->newInstance();
    }

    public function getNewModelQuery(): BaseBuilder|Builder
    {
        return $this->budgetColumnSetting->newModelQuery();
    }

    public function getAll(): Collection
    {
        return $this->getNewModelQuery()->get();
    }

    public function findByColumnPosition(int $position): BudgetColumnSetting|null
    {
        return $this->getNewModelQuery()->byColumnPosition($position)->first();
    }
}
