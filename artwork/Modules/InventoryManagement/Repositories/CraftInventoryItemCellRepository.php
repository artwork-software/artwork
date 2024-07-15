<?php

namespace Artwork\Modules\InventoryManagement\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryItemCell;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as BaseBuilder;

class CraftInventoryItemCellRepository extends BaseRepository
{
    public function __construct(
        private readonly CraftInventoryItemCell $craftInventoryItemCell
    ) {
    }

    public function getNewModelInstance(array $attributes = []): CraftInventoryItemCell
    {
        return $this->craftInventoryItemCell->newInstance($attributes);
    }

    public function getNewModelQuery(): BaseBuilder|Builder
    {
        /** @var BaseBuilder|Builder $builder */
        $builder = $this->craftInventoryItemCell->newModelQuery();

        return $builder;
    }
}
