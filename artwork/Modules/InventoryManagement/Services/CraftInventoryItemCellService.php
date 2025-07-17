<?php

namespace Artwork\Modules\InventoryManagement\Services;

use Artwork\Modules\Inventory\Enums\CraftsInventoryColumnTypeEnum;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryItemCell;
use Artwork\Modules\InventoryManagement\Models\CraftsInventoryColumn;
use Artwork\Modules\InventoryManagement\Repositories\CraftInventoryItemCellRepository;
use Illuminate\Auth\AuthManager;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

class CraftInventoryItemCellService
{
    public function __construct(
        private readonly CraftInventoryItemCellRepository $craftInventoryItemCellRepository,
        private readonly AuthManager $authManager
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

        // get last edit column from craft_inventory_item_id
        // get last edit column from all columns
        // update last edit column with current date and user

        $column = CraftsInventoryColumn::where('type', CraftsInventoryColumnTypeEnum::LAST_EDIT_AND_EDITOR)->first();

        $cell = CraftInventoryItemCell::where(
            'craft_inventory_item_id',
            $craftInventoryItemCell->craft_inventory_item_id
        )
            ->where('crafts_inventory_column_id', $column->id)
            ->first();

        $this->craftInventoryItemCellRepository->updateOrFail(
            $cell,
            [
                'cell_value' => json_encode([
                    'editor' => [
                        'id' => $this->authManager->id(),
                        'first_name' => $this->authManager->user()->getAttribute('first_name'),
                        'last_name' => $this->authManager->user()->getAttribute('last_name'),
                        'full_name' => $this->authManager->user()->getAttribute('full_name'),
                        'email' => $this->authManager->user()->getAttribute('email'),
                        'business' => $this->authManager->user()->getAttribute('business'),
                        'position' => $this->authManager->user()->getAttribute('position'),
                        'phone_number' => $this->authManager->user()->getAttribute('phone_number'),
                        'description' => $this->authManager->user()->getAttribute('description'),
                        'profile_photo_url' => urldecode($this->authManager->user()->getAttribute('profile_photo_url'))
                    ],
                    'date' => now()->translatedFormat('d.m.Y H:i:s')
                ], JSON_THROW_ON_ERROR)
            ]
        );
    }

    public function getNameForSchedulingFromCells(Collection $cells): string
    {
        /** @var CraftInventoryItemCell $cell */
        /*$cell = $cells->first(function (CraftInventoryItemCell $cell): bool {
            return is_string($cell->getAttribute('cell_value'));
        });
        return strval($cell?->getAttribute('cell_value'));*/

        // get the name from the first cell where the column id is 1
        $cell = $cells->firstWhere('crafts_inventory_column_id', 1);
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
