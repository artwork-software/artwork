<?php

namespace Artwork\Modules\InventoryManagement\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryCategory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as BaseBuilder;

class CraftInventoryCategoryRepository extends BaseRepository
{
    public function __construct(private readonly CraftInventoryCategory $craftInventoryCategory)
    {
    }

    public function getModelQuery(): BaseBuilder|Builder
    {
        /** @var BaseBuilder|Builder $builder */
        $builder = $this->craftInventoryCategory->newModelQuery();

        return $builder;
    }

    public function find(int $id): CraftInventoryCategory
    {
        /** @var CraftInventoryCategory $craftInventoryCategory */
        $craftInventoryCategory = $this->getModelQuery()->find($id);

        return $craftInventoryCategory;
    }

    public function getAllByCraftIdOrderedByOrder(int $craftId): Collection
    {
        return $this->getModelQuery()
            ->where('craft_id', $craftId)
            ->orderBy('order')
            ->get();
    }

    public function getCategoryCountForCraft(int $craftId): int
    {
        return $this->getModelQuery()
            ->where('craft_id', $craftId)
            ->count();
    }
}
