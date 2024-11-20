<?php

namespace Artwork\Modules\InventoryManagement\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryGroupFolder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as BaseBuilder;

class CraftsInventoryGroupFolderRepository extends BaseRepository
{
    public function __construct(private readonly CraftInventoryGroupFolder $craftInventoryGroupFolder)
    {
    }

    public function getNewModelInstance(array $attributes = []): CraftInventoryGroupFolder
    {
        return $this->craftInventoryGroupFolder->newInstance($attributes);
    }

    public function getNewModelQuery(): BaseBuilder|Builder
    {
        /** @var BaseBuilder|Builder $builder */
        $builder = $this->craftInventoryGroupFolder->newModelQuery();

        return $builder;
    }
}
