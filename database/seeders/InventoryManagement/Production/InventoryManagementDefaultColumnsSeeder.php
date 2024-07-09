<?php

namespace Database\Seeders\InventoryManagement\Production;

use Artwork\Modules\InventoryManagement\Enums\CraftsInventoryColumnTypeEnum;
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
            ''
        );

        $craftsInventoryColumnService->create(
            'Anzahl',
            CraftsInventoryColumnTypeEnum::TEXT,
            '',
            [],
            ''
        );

        $craftsInventoryColumnService->create(
            'Kommentar',
            CraftsInventoryColumnTypeEnum::TEXT,
            '',
            [],
            ''
        );
    }
}
