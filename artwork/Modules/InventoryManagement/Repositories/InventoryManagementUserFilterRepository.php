<?php

namespace Artwork\Modules\InventoryManagement\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\InventoryManagement\Models\InventoryManagementUserFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as BaseBuilder;

class InventoryManagementUserFilterRepository extends BaseRepository
{
    public function __construct(private readonly InventoryManagementUserFilter $inventoryManagementUserFilter)
    {
    }

    public function getNewModelInstance(array $attributes = []): InventoryManagementUserFilter
    {
        return $this->inventoryManagementUserFilter->newInstance($attributes);
    }

    public function getNewModelQuery(): BaseBuilder|Builder
    {
        /** @var BaseBuilder|Builder $builder */
        $builder = $this->inventoryManagementUserFilter->newModelQuery();

        return $builder;
    }

    public function findForUser(int $id): InventoryManagementUserFilter|null
    {
        /** @var InventoryManagementUserFilter|null $filter */
        $filter = $this->getNewModelQuery()
            ->where('user_id', $id)
            ->first();

        return $filter;
    }
}
