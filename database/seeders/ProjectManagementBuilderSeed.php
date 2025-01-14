<?php

namespace Database\Seeders;

use Artwork\Modules\ProjectManagementBuilder\Models\ProjectManagementBuilder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProjectManagementBuilderSeed extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $dataSet = [
            [
                'name' => 'Project Title',
                'order' => 1,
                'is_active' => true,
                'component' => 'ProjectTitleComponent',
                'deletable' => false
            ],
            [
                'name' => 'Actions',
                'order' => 2,
                'is_active' => true,
                'component' => 'ActionsComponent',
                'deletable' => false
            ],
        ];

        foreach ($dataSet as $data) {
            ProjectManagementBuilder::create($data);
        }
    }
}
