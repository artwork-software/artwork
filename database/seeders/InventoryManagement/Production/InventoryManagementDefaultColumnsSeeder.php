<?php

namespace Database\Seeders\InventoryManagement\Production;

use Artwork\Modules\Inventory\Enums\CraftsInventoryColumnTypeEnum;
use Artwork\Modules\InventoryManagement\Services\CraftsInventoryColumnService;
use Illuminate\Database\Seeder;
use Throwable;

class InventoryManagementDefaultColumnsSeeder extends Seeder
{
    /**
     * Adds default columns to crafts_inventory_columns table
     *
     * @return void
     * @throws Throwable
     */
    public function run(): void
    {
        /** @var CraftsInventoryColumnService $craftsInventoryColumnService */
        $craftsInventoryColumnService = app()->make(CraftsInventoryColumnService::class);

        $craftsInventoryColumnService->create(
            'Name',
            CraftsInventoryColumnTypeEnum::TEXT,
            '',
            [],
            '',
            false,
        );

        $craftsInventoryColumnService->create(
            'Anzahl',
            CraftsInventoryColumnTypeEnum::TEXT,
            '',
            [],
            '',
            false
        );

        $craftsInventoryColumnService->create(
            'Letzte Änderung',
            CraftsInventoryColumnTypeEnum::LAST_EDIT_AND_EDITOR,
            '',
            [],
            '',
            false
        );
    }
}
