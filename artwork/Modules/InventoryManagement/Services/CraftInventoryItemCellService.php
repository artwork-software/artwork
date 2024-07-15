<?php

namespace Artwork\Modules\InventoryManagement\Services;

use Artwork\Modules\InventoryManagement\Models\CraftInventoryItemCell;
use Artwork\Modules\InventoryManagement\Repositories\CraftInventoryItemCellRepository;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

class CraftInventoryItemCellService
{
    public function __construct(
        private readonly CraftInventoryItemCellRepository $craftInventoryItemCellRepository
    ) {
    }

    /**
     * @throws Throwable
     */
    public function create(
        int $columnId,
        int $itemId,
        string $cellValue = ''
    ): CraftInventoryItemCell {
        $craftInventoryItemCell = $this->craftInventoryItemCellRepository->getNewModelInstance(
            [
                'crafts_inventory_column_id' => $columnId,
                'craft_inventory_item_id' => $itemId,
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

    public function getNameForSchedulingFromCells(Collection $cells): string
    {
        /** @var CraftInventoryItemCell $cell */
        $cell = $cells->first(function (CraftInventoryItemCell $cell): bool {
            return is_string($cell->getAttribute('cell_value'));
        });
        return strval($cell?->getAttribute('cell_value'));
    }

    public function getItemCountForSchedulingFromCells(Collection $cells): int
    {
        /** @var CraftInventoryItemCell $cell */
        $cell = $cells->first(function (CraftInventoryItemCell $cell): bool {
            return is_numeric($cell->getAttribute('cell_value'));
        });
        return intval($cell?->getAttribute('cell_value'));
    }
}
