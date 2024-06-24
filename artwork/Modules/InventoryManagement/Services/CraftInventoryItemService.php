<?php

namespace Artwork\Modules\InventoryManagement\Services;

use Artwork\Modules\InventoryManagement\Models\CraftInventoryItem;
use Artwork\Modules\InventoryManagement\Models\CraftsInventoryColumn;
use Artwork\Modules\InventoryManagement\Repositories\CraftInventoryItemCellRepository;
use Artwork\Modules\InventoryManagement\Repositories\CraftInventoryItemRepository;
use Artwork\Modules\InventoryManagement\Repositories\CraftsInventoryColumnRepository;
use Throwable;

readonly class CraftInventoryItemService
{
    public function __construct(
        private CraftsInventoryColumnRepository $craftsInventoryColumnRepository,
        private CraftInventoryItemRepository $craftInventoryItemRepository,
        private CraftInventoryItemCellService $craftInventoryItemCellService
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
            function (CraftsInventoryColumn $column, int $index) use ($craftInventoryItem, $order): void {
                $this->craftInventoryItemCellService->create(
                    $column->id,
                    $craftInventoryItem->id,
                    ($index + 1),
                    $column->type,
                    ''
                );
            }
        );

        return $craftInventoryItem;
    }

//    /**
//     * @throws Throwable
//     */
//    public function updateOrder(CraftInventoryGroup $craftInventoryGroup, int $order): void
//    {
//    }
//
//    public function forceDelete(int|CraftInventoryGroup $craftInventoryGroup): bool
//    {
//        if (!$craftInventoryGroup instanceof CraftInventoryGroup) {
//            $craftInventoryGroup = $this->craftsInventoryGroupRepository->find($craftInventoryGroup);
//        }
//
//        return $this->craftsInventoryGroupRepository->forceDelete($craftInventoryGroup);
//    }
}
