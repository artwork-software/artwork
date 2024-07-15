<?php

namespace Artwork\Modules\InventoryManagement\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\InventoryManagement\Models\CraftsInventoryColumn;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as BaseBuilder;

class CraftsInventoryColumnRepository extends BaseRepository
{
    public function __construct(
        private readonly CraftsInventoryColumn $craftsInventoryColumn
    ) {
    }

    public function getNewModelInstance(array $attributes = []): CraftsInventoryColumn
    {
        return $this->craftsInventoryColumn->newInstance($attributes);
    }

    public function getNewModelQuery(): BaseBuilder|Builder
    {
        /** @var BaseBuilder|Builder $builder */
        $builder = $this->craftsInventoryColumn->newModelQuery();

        return $builder;
    }

    public function find(int $id): CraftsInventoryColumn|null
    {
        /** @var CraftsInventoryColumn|null $column */
        $column = $this->getNewModelQuery()->find($id);

        return $column;
    }

    public function getAllOrdered($orderBy = 'id', $orderByDirection = 'asc'): Collection
    {
        return $this->getNewModelQuery()
            ->orderBy($orderBy, $orderByDirection)
            ->get(['id', 'name', 'type', 'type_options', 'background_color']);
    }

    public function getAllItemCells(CraftsInventoryColumn $craftsInventoryColumn): Collection
    {
        return $craftsInventoryColumn->cells()->get();
    }
}
