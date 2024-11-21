<?php

namespace Artwork\Modules\InventoryManagement\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\InventoryManagement\Models\CraftsInventoryColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
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

    public function getAllOrdered(): Collection
    {
        return $this->getNewModelQuery()
            ->orderBy('order')
            ->get(['id', 'name', 'type', 'type_options', 'background_color', 'deletable', 'order']);
    }

    public function getAllItemCells(CraftsInventoryColumn $craftsInventoryColumn): Collection
    {
        return $craftsInventoryColumn->cells()->get();
    }

    public function getMaxOrder(): int
    {
        return $this->getNewModelQuery()->max('order') ?? 0;
    }
}
