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
}
