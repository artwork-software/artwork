<?php

namespace Artwork\Modules\InventoryManagement\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryGroup;
use Illuminate\Database\Eloquent\Collection;

class CraftInventoryGroupRepository extends BaseRepository
{
    public function find(int $id): CraftInventoryGroup
    {
        /** @var CraftInventoryGroup $craftInventoryGroup */
        $craftInventoryGroup = CraftInventoryGroup::find($id);

        return $craftInventoryGroup;
    }

    public function getAllByCategoryIdOrderedByOrder(int $categoryId): Collection
    {
        return CraftInventoryGroup::query()
            ->where('craft_inventory_category_id', $categoryId)
            ->orderBy('order')
            ->get();
    }

    public function getGroupCountForCategory(int $categoryId): int
    {
        return CraftInventoryGroup::query()
            ->where('craft_inventory_category_id', $categoryId)
            ->count();
    }
}
