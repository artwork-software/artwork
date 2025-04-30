<?php

namespace Database\Seeders;

use Artwork\Modules\Inventory\Models\InventoryArticleStatus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InventoryArticleStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $dataSet = [
            [
                'name' => 'Einsatzbereit',
                'default' => true,
                'deletable' => false,
            ],
            [
                'name' => 'Defekt',
                'deletable' => false,
            ],
            [
                'name' => 'Ausgesondert',
                'deletable' => false,
            ],
            [
                'name' => 'Nicht auffindbar',
                'deletable' => false,
            ],
            [
                'name' => 'fest verbaut',
                'deletable' => false,
            ],
        ];

        foreach ($dataSet as $data) {
            InventoryArticleStatus::updateOrCreate(
                [
                    'name' => $data['name'],
                ],
                [
                    'default' => $data['default'] ?? false,
                    'deletable' => $data['deletable'] ?? true,
                ]
            );
        }
    }
}
