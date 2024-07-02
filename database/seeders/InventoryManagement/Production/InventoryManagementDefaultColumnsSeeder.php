<?php

namespace Database\Seeders\InventoryManagement\Production;

use Artwork\Modules\InventoryManagement\Enums\CraftsInventoryColumnTypeEnum;
use Artwork\Modules\InventoryManagement\Services\CraftsInventoryColumnService;
use Illuminate\Database\Seeder;
use Throwable;

class InventoryManagementDefaultColumnsSeeder extends Seeder
{
    public function __construct(
        private readonly CraftsInventoryColumnService $craftsInventoryColumnService
    ) {
    }


    /**
     * Adds default columns to crafts_inventory_columns table
     *
     * @return void
     * @throws Throwable
     */
    public function run(): void
    {
        $this->craftsInventoryColumnService->create(
            'Name',
            CraftsInventoryColumnTypeEnum::TEXT
        );

        $this->craftsInventoryColumnService->create(
            'Anzahl',
            CraftsInventoryColumnTypeEnum::TEXT
        );

        $this->craftsInventoryColumnService->create(
            'Kommentar',
            CraftsInventoryColumnTypeEnum::TEXT
        );
    }
}
