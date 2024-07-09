<?php

namespace Artwork\Modules\InventoryManagement\Services;

use Artwork\Modules\InventoryManagement\Models\InventoryManagementUserFilter;
use Artwork\Modules\InventoryManagement\Repositories\InventoryManagementUserFilterRepository;
use Illuminate\Support\Collection;
use Throwable;

class InventoryManagementUserFilterService
{
    public function __construct(
        private readonly InventoryManagementUserFilterRepository $inventoryManagementUserFilterRepository
    ) {
    }

    /**
     * @param int $id
     * @return array<int, int>
     */
    public function getFilterOfUser(int $id): array
    {
        $inventoryManagementUserFilter = $this->inventoryManagementUserFilterRepository->findForUser($id);

        if ($inventoryManagementUserFilter) {
            return $inventoryManagementUserFilter->getAttribute('filter');
        }

        return [];
    }

    /**
     * @throws Throwable
     */
    public function updateOrCreate(int $userId, Collection $craftIds): void
    {
        $attributes = [
            'user_id' => $userId,
            'filter' => $craftIds
        ];
        $filter = $this->inventoryManagementUserFilterRepository->findForUser($userId);
        if (!$filter instanceof InventoryManagementUserFilter) {
            $this->inventoryManagementUserFilterRepository->saveOrFail(
                $this->inventoryManagementUserFilterRepository->getNewModelInstance($attributes)
            );
            return;
        }

        $this->inventoryManagementUserFilterRepository->updateOrFail($filter, $attributes);
    }
}
