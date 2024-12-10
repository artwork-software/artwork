<?php

namespace Artwork\Modules\Budget\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Budget\Models\TableColumnOrder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Support\Collection;

class TableColumnOrderRepository extends BaseRepository
{
    public function __construct(private readonly TableColumnOrder $tableColumnOrder)
    {
    }

    public function getNewModelInstance(): TableColumnOrder
    {
        return $this->tableColumnOrder->newInstance();
    }

    public function getNewModelQuery(): BaseBuilder|Builder
    {
        return $this->tableColumnOrder->newModelQuery();
    }

    public function getAllOrderedByPosition(): Collection
    {
        return $this->getNewModelQuery()->orderBy('position')->get();
    }
}
