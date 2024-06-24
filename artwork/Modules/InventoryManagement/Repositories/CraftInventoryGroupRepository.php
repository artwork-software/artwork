<?php

namespace Artwork\Modules\InventoryManagement\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryGroup;

readonly class CraftInventoryGroupRepository extends BaseRepository
{
    public function find(int $id): CraftInventoryGroup
    {
        /** @var CraftInventoryGroup $craftInventoryGroup */
        $craftInventoryGroup = CraftInventoryGroup::find($id);

        return $craftInventoryGroup;
    }
}
