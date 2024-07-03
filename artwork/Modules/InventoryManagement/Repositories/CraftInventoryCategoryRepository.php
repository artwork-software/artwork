<?php

namespace Artwork\Modules\InventoryManagement\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryCategory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;

class CraftInventoryCategoryRepository extends BaseRepository
{
    public function __construct(private readonly CraftInventoryCategory $craftInventoryCategory)
    {
    }

    public function getQuery(): Builder
    {
        /** @var Builder $builder */
        $builder = $this->craftInventoryCategory->newModelQuery();

        return $builder;
    }

    public function find(int $id): CraftInventoryCategory
    {
        /** @var CraftInventoryCategory $craftInventoryCategory */
        $craftInventoryCategory = $this->getQuery()->find($id);

        return $craftInventoryCategory;
    }

    public function getAllByCraftIdOrderedByOrder(int $craftId): Collection
    {
        return $this->getQuery()
            ->where('craft_id', $craftId)
            ->orderBy('order')
            ->get();
    }

    public function getCategoryCountForCraft(int $craftId): int
    {
        return $this->getQuery()
            ->where('craft_id', $craftId)
            ->count();
    }
}
