<?php

namespace Artwork\Modules\InventoryManagement\Services;

use Artwork\Modules\InventoryManagement\Models\CraftInventoryGroup;
use Artwork\Modules\InventoryManagement\Repositories\CraftInventoryGroupRepository;
use Throwable;

readonly class CraftInventoryGroupService
{
    public function __construct(
        private CraftInventoryGroupRepository $craftsInventoryGroupRepository,
        private InventoryResourceCalculateModelsOrderService $inventoryResourceCalculateModelsOrderService
    ) {
    }

    public function getNewCraftInventoryGroup(array $attributes): CraftInventoryGroup
    {
        return new CraftInventoryGroup($attributes);
    }

    /**
     * @throws Throwable
     */
    public function create(
        int $categoryId,
        string $name
    ): CraftInventoryGroup {
        $craftsInventoryGroup = $this->getNewCraftInventoryGroup(
            [
                'craft_inventory_category_id' => $categoryId,
                'name' => $name,
                'order' => $this->craftsInventoryGroupRepository->getGroupCountForCategory($categoryId)
            ]
        );
        $this->craftsInventoryGroupRepository->saveOrFail($craftsInventoryGroup);

        return $craftsInventoryGroup;
    }

    /**
     * @throws Throwable
     */
    public function updateName(string $name, CraftInventoryGroup $craftInventoryGroup): void
    {
        $this->craftsInventoryGroupRepository->updateOrFail(
            $craftInventoryGroup,
            [
                'name' => $name
            ]
        );
    }

    /**
     * @throws Throwable
     */
    public function updateOrder(CraftInventoryGroup $craftInventoryGroup, int $order): void
    {
        foreach (
            $this->inventoryResourceCalculateModelsOrderService->getReorderedModels(
                $this->craftsInventoryGroupRepository
                    ->getAllByCategoryIdOrderedByOrder($craftInventoryGroup->craft_inventory_category_id),
                $order,
                $craftInventoryGroup
            ) as $index => $orderedGroup
        ) {
            $this->craftsInventoryGroupRepository->updateOrFail(
                $orderedGroup,
                [
                    'order' => $index
                ]
            );
        }
    }

    public function forceDelete(int|CraftInventoryGroup $craftInventoryGroup): bool
    {
        if (!$craftInventoryGroup instanceof CraftInventoryGroup) {
            $craftInventoryGroup = $this->craftsInventoryGroupRepository->find($craftInventoryGroup);
        }

        return $this->craftsInventoryGroupRepository->forceDelete($craftInventoryGroup);
    }
}
