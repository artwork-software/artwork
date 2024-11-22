<?php

namespace Artwork\Modules\InventoryManagement\Services;

use Artwork\Modules\InventoryManagement\Models\CraftInventoryItem;
use Artwork\Modules\InventoryManagement\Models\CraftsInventoryColumn;
use Artwork\Modules\InventoryManagement\Repositories\CraftInventoryItemRepository;
use Artwork\Modules\InventoryManagement\Repositories\CraftsInventoryColumnRepository;
use Throwable;

class CraftInventoryItemService
{
    public function __construct(
        private readonly CraftsInventoryColumnRepository $craftsInventoryColumnRepository,
        private readonly CraftInventoryItemRepository $craftInventoryItemRepository,
        private readonly CraftInventoryItemCellService $craftInventoryItemCellService,
        private readonly InventoryResourceCalculateModelsOrderService $inventoryResourceCalculateModelsOrderService
    ) {
    }

    /**
     * @throws Throwable
     */
    public function create(
        int $order,
        ?int $groupId,
        ?int $folderId
    ): CraftInventoryItem {
        $craftInventoryItem = $this->craftInventoryItemRepository->getNewModelInstance(
            [
                'craft_inventory_group_id' => $groupId,
                'craft_inventory_group_folder_id' => $folderId,
                'order' => $order
            ]
        );
        $this->craftInventoryItemRepository->saveOrFail($craftInventoryItem);

        /** @var CraftsInventoryColumn $column */
        foreach ($this->craftsInventoryColumnRepository->getAllOrdered() as $column) {
            $this->craftInventoryItemCellService->create(
                $column->getAttribute('id'),
                $craftInventoryItem->getAttribute('id')
            );
        }

        return $craftInventoryItem;
    }

    /**
     * @throws Throwable
     */
    public function createCellsInItemsForColumn(
        CraftsInventoryColumn $craftsInventoryColumn,
        string $cellValue = ''
    ): void {
        /** @var CraftInventoryItem $craftInventoryItem */
        foreach ($this->craftInventoryItemRepository->getAll() as $craftInventoryItem) {
            $this->craftInventoryItemCellService->create(
                $craftsInventoryColumn->getAttribute('id'),
                $craftInventoryItem->getAttribute('id'),
                $cellValue
            );
        }
    }

    /**
     * @throws Throwable
     */
    public function updateOrder(CraftInventoryItem $craftInventoryItem, int $order): void
    {
        foreach (
            $this->inventoryResourceCalculateModelsOrderService->getReorderedModels(
                $this->craftInventoryItemRepository->getAllOfGroupOrderedByOrder(
                    $craftInventoryItem->getAttribute('craft_inventory_group_id')
                ),
                $order,
                $craftInventoryItem
            ) as $index => $orderedItem
        ) {
            $this->craftInventoryItemRepository->updateOrFail(
                $orderedItem,
                [
                    'order' => $index
                ]
            );
        }
    }

    public function forceDelete(int|CraftInventoryItem $craftInventoryItem): bool
    {
        if (!$craftInventoryItem instanceof CraftInventoryItem) {
            $craftInventoryItem = $this->craftInventoryItemRepository->find($craftInventoryItem);
        }

        return $this->craftInventoryItemRepository->forceDelete($craftInventoryItem);
    }
}
