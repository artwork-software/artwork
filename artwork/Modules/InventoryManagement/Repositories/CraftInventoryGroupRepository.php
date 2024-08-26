<?php

namespace Artwork\Modules\InventoryManagement\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryGroup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder as BaseBuilder;

class CraftInventoryGroupRepository extends BaseRepository
{
    public function __construct(private readonly CraftInventoryGroup $craftInventoryGroup)
    {
    }

    public function getNewModelInstance(array $attributes = []): CraftInventoryGroup
    {
        return $this->craftInventoryGroup->newInstance($attributes);
    }

    public function getNewModelQuery(): BaseBuilder|Builder
    {
        /** @var BaseBuilder|Builder $builder */
        $builder = $this->craftInventoryGroup->newModelQuery();

        return $builder;
    }

    public function getAllByCategoryIdOrderedByOrder(int $categoryId): Collection
    {
        return $this->getNewModelQuery()
            ->where('craft_inventory_category_id', $categoryId)
            ->orderBy('order')
            ->get();
    }

    public function getGroupCountForCategory(int $categoryId): int
    {
        return $this->getNewModelQuery()
            ->where('craft_inventory_category_id', $categoryId)
            ->count();
    }
}
