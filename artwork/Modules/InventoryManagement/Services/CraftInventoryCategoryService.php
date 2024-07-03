<?php

namespace Artwork\Modules\InventoryManagement\Services;

use Artwork\Modules\InventoryManagement\Models\CraftInventoryCategory;
use Artwork\Modules\InventoryManagement\Repositories\CraftInventoryCategoryRepository;
use Throwable;

class CraftInventoryCategoryService
{
    public function __construct(
        private readonly CraftInventoryCategoryRepository $craftInventoryCategoryRepository,
        private readonly InventoryResourceCalculateModelsOrderService $inventoryResourceCalculateModelsOrderService
    ) {
    }

    /**
     * @throws Throwable
     */
    public function create(
        int $craftId,
        string $name
    ): CraftInventoryCategory {
        $craftsInventoryCategory = $this->craftInventoryCategoryRepository->getNewModelInstance(
            [
                'craft_id' => $craftId,
                'name' => $name,
                'order' => $this->craftInventoryCategoryRepository->getCategoryCountForCraft($craftId)
            ]
        );
        $this->craftInventoryCategoryRepository->saveOrFail($craftsInventoryCategory);

        return $craftsInventoryCategory;
    }

    /**
     * @throws Throwable
     */
    public function updateName(string $name, CraftInventoryCategory $craftInventoryCategory): void
    {
        $this->craftInventoryCategoryRepository->updateOrFail(
            $craftInventoryCategory,
            [
                'name' => $name
            ]
        );
    }

    /**
     * @throws Throwable
     */
    public function updateOrder(CraftInventoryCategory $craftInventoryCategory, int $order): void
    {
        foreach (
            $this->inventoryResourceCalculateModelsOrderService->getReorderedModels(
                $this->craftInventoryCategoryRepository
                    ->getAllByCraftIdOrderedByOrder($craftInventoryCategory->craft_id),
                $order,
                $craftInventoryCategory
            ) as $index => $orderedCategory
        ) {
            $this->craftInventoryCategoryRepository->updateOrFail(
                $orderedCategory,
                [
                    'order' => $index
                ]
            );
        }
    }

    public function forceDelete(int|CraftInventoryCategory $craftInventoryCategory): bool
    {
        if (!$craftInventoryCategory instanceof CraftInventoryCategory) {
            $craftInventoryCategory = $this->craftInventoryCategoryRepository->find($craftInventoryCategory);
        }

        return $this->craftInventoryCategoryRepository->forceDelete($craftInventoryCategory);
    }
}
