<?php

namespace Database\Seeders\InventoryManagement\Dev;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryItem;
use Artwork\Modules\InventoryManagement\Models\CraftsInventoryColumn;
use Database\Seeders\BenchmarkProjectSeeder;
use Database\Seeders\InventoryManagement\Production\InventoryManagementDefaultColumnsSeeder;
use Illuminate\Database\Seeder;

class InventoryManagementDevSeeder extends Seeder
{
    /**
     * Adds whole inventory management with multiple categories, groups and items
     *
     * @return void
     */
    public function run(): void
    {
        //add default columns
        $this->call(InventoryManagementDefaultColumnsSeeder::class);
    }
}
