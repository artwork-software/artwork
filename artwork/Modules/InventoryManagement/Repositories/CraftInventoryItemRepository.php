<?php

namespace Artwork\Modules\InventoryManagement\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Database\Eloquent\Collection;

class CraftInventoryItemRepository extends BaseRepository
{
    public function __construct(
        private readonly CraftInventoryItem $craftInventoryItem
    ) {
    }

    public function getNewModelInstance(array $attributes = []): CraftInventoryItem
    {
        return $this->craftInventoryItem->newInstance($attributes);
    }

    public function getNewModelQuery(): BaseBuilder|Builder
    {
        /** @var BaseBuilder|Builder $builder */
        $builder = $this->craftInventoryItem->newModelQuery();

        return $builder;
    }

    public function getAll(): Collection
    {
        return $this->getNewModelQuery()->get();
    }

    public function find(int $id): CraftInventoryItem|null
    {
        /** @var CraftInventoryItem|null $craftInventoryItem */
        $craftInventoryItem = $this->getNewModelQuery()->find($id);

        return $craftInventoryItem;
    }

    public function getAllOfGroupOrderedByOrder(int $id): Collection
    {
        return $this->getNewModelQuery()
            ->where('craft_inventory_group_id', $id)
            ->orderBy('order')
            ->get();
    }
}
