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
        InventoryArticleProperties::firstOrCreate(
            ['type' => 'room'],
            [
                'name' => 'Raum',
                'is_filterable' => false,
                'show_in_list' => false,
                'is_required' => false,
                'is_deletable' => false,
            ]
        );

        InventoryArticleProperties::firstOrCreate(
            ['type' => 'manufacturer'],
            [
                'name' => 'Hersteller',
                'is_filterable' => true,
                'show_in_list' => true,
                'is_required' => false,
                'is_deletable' => false,
            ]
        );
    }
}
