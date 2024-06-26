<?php

namespace Artwork\Modules\InventoryManagement\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryItem;
use Illuminate\Database\Eloquent\Collection;

readonly class CraftInventoryItemRepository extends BaseRepository
{
    public function getAll(): Collection
    {
        return CraftInventoryItem::all();
    }

    public function find(int $id): CraftInventoryItem
    {
        /** @var CraftInventoryItem $craftInventoryItem */
        $craftInventoryItem = CraftInventoryItem::find($id);

        return $craftInventoryItem;
    }

    public function getAllOfGroupOrderedByOrder(int $id): Collection
    {
        return CraftInventoryItem::query()
            ->where('craft_inventory_group_id', $id)
            ->orderBy('order')
            ->get();
    }
}
