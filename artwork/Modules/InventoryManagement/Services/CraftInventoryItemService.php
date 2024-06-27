<?php

namespace Artwork\Modules\InventoryManagement\Services;

use Artwork\Modules\InventoryManagement\Models\CraftInventoryItem;
use Artwork\Modules\InventoryManagement\Models\CraftsInventoryColumn;
use Artwork\Modules\InventoryManagement\Repositories\CraftInventoryItemRepository;
use Artwork\Modules\InventoryManagement\Repositories\CraftsInventoryColumnRepository;
use Throwable;

readonly class CraftInventoryItemService
{
    public function __construct(
        private CraftsInventoryColumnRepository $craftsInventoryColumnRepository,
        private CraftInventoryItemRepository $craftInventoryItemRepository,
        private CraftInventoryItemCellService $craftInventoryItemCellService,
        private InventoryResourceCalculateModelsOrderService $inventoryResourceCalculateModelsOrderService
    ) {
    }

    public function getNewCraftInventoryItem(array $attributes): CraftInventoryItem
    {
        return new CraftInventoryItem($attributes);
    }

    /**
     * @throws Throwable
     */
    public function create(
        int $groupId,
        int $order
    ): CraftInventoryItem {
        $craftInventoryItem = $this->getNewCraftInventoryItem(
            [
                'craft_inventory_group_id' => $groupId,
                'order' => $order
            ]
        );
        $this->craftInventoryItemRepository->saveOrFail($craftInventoryItem);

        $this->craftsInventoryColumnRepository->getAllOrdered()->each(
            function (CraftsInventoryColumn $column) use ($craftInventoryItem): void {
                $this->craftInventoryItemCellService->create(
                    $column->id,
                    $craftInventoryItem->id
                );
            }
        );

        return $craftInventoryItem;
    }

    /**
     * @throws Throwable
     */
    public function createCellsInItemsForColumn(
        CraftsInventoryColumn $craftsInventoryColumn,
        string $cellValue = ''
    ): void {
        $this->craftInventoryItemRepository->getAll()->each(
            /**
             * @throws Throwable
             */
            function (CraftInventoryItem $craftInventoryItem) use ($cellValue, $craftsInventoryColumn): void {
                $this->craftInventoryItemCellService->create(
                    $craftsInventoryColumn->id,
                    $craftInventoryItem->id,
                    $cellValue
                );
            }
        );
    }

    /**
     * @throws Throwable
     */
    public function updateOrder(CraftInventoryItem $craftInventoryItem, int $order): void
    {
        foreach (
            $this->inventoryResourceCalculateModelsOrderService->getReorderedModels(
                $this->craftInventoryItemRepository
                    ->getAllOfGroupOrderedByOrder($craftInventoryItem->craft_inventory_group_id),
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
