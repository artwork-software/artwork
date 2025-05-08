<?php

namespace Database\Seeders;

use Artwork\Modules\Inventory\Models\InventoryArticleProperties;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InventoryArticlePropertiesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        InventoryArticleProperties::create([
            'name' => 'Raum',
            'type' => 'room',
            'is_filterable' => false,
            'show_in_list' => false,
            'is_required' => false,
            'is_deletable' => false,
        ]);

        // hersteller
        InventoryArticleProperties::create([
            'name' => 'Hersteller',
            'type' => 'manufacturer',
            'is_filterable' => true,
            'show_in_list' => true,
            'is_required' => false,
            'is_deletable' => false,
        ]);
    }
}
