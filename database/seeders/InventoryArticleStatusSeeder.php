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
                'color' => '#16A34A'
            ],
            [
                'name' => 'Defekt',
                'deletable' => false,
                'color' => '#EF4444'
            ],
            [
                'name' => 'Ausgesondert',
                'deletable' => false,
                'color' => '#F59E0B'
            ],
            [
                'name' => 'Nicht auffindbar',
                'deletable' => false,
                'color' => '#6B7280'
            ],
            [
                'name' => 'fest verbaut',
                'deletable' => false,
                'color' => '#3B82F6'
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
                    'color' => $data['color'] ?? null,
                ]
            );
        }
    }
}
