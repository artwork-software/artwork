<?php

namespace Artwork\Modules\InventoryManagement\Services;

use Artwork\Modules\InventoryManagement\Http\Requests\ItemCell\UpdateCraftInventoryItemCellCellValueRequest;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryGroup;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryItemCell;
use Artwork\Modules\InventoryManagement\Repositories\CraftInventoryItemCellRepository;
use Throwable;

readonly class CraftInventoryItemCellService
{
    public function __construct(
        private CraftInventoryItemCellRepository $craftInventoryItemCellRepository
    ) {
    }

    public function getNewCraftInventoryItemCell(array $attributes): CraftInventoryItemCell
    {
        return new CraftInventoryItemCell($attributes);
    }

    /**
     * @throws Throwable
     */
    public function create(
        int $columnId,
        int $itemId,
        int $order,
        string $cellType,
        string $cellValue
    ): CraftInventoryItemCell {
        $craftInventoryItemCell = $this->getNewCraftInventoryItemCell(
            [
                'crafts_inventory_column_id' => $columnId,
                'craft_inventory_item_id' => $itemId,
                'order' => $order,
                'cell_type' => $cellType,
                'cell_value' => $cellValue
            ]
        );
        $this->craftInventoryItemCellRepository->saveOrFail($craftInventoryItemCell);

        return $craftInventoryItemCell;
    }

    /**
     * @throws Throwable
     */
    public function updateCellValue(string $cellValue, CraftInventoryItemCell $craftInventoryItemCell): void
    {
        $this->craftInventoryItemCellRepository->updateOrFail(
            $craftInventoryItemCell,
            [
                'cell_value' => $cellValue
            ]
        );
    }
}
