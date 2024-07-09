<?php

namespace Artwork\Modules\InventoryManagement\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryCategory;
use Illuminate\Database\Eloquent\Collection;

readonly class CraftInventoryCategoryRepository extends BaseRepository
{
    public function __construct()
    {
    }

    public function find(int $id): CraftInventoryCategory
    {
        /** @var CraftInventoryCategory $craftInventoryCategory */
        $craftInventoryCategory = CraftInventoryCategory::find($id);

        return $craftInventoryCategory;
    }

    public function getAllByCraftIdOrderedByOrder(int $craftId): Collection
    {
        return CraftInventoryCategory::query()
            ->where('craft_id', $craftId)
            ->orderBy('order')
            ->get();
    }

    public function getCategoryCountForCraft(int $craftId): int
    {
        return CraftInventoryCategory::query()
            ->where('craft_id', $craftId)
            ->count();
    }
}
