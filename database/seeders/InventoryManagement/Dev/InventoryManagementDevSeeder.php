<?php

namespace Database\Seeders\InventoryManagement\Dev;

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

        //create categories, groups, items
    }
}
