<?php

namespace Artwork\Modules\InventoryManagement\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\InventoryManagement\Models\InventoryManagementUserFilter;

readonly class InventoryManagementUserFilterRepository extends BaseRepository
{
    public function findForUser(int $id): InventoryManagementUserFilter|null
    {
        /** @var InventoryManagementUserFilter|null $filter */
        $filter = InventoryManagementUserFilter::query()->where('user_id', $id)->first();

        return $filter;
    }
}
