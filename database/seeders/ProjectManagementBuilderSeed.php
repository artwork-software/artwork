<?php

namespace Database\Seeders;

use Artwork\Modules\Project\Models\ProjectManagementBuilder;
use Artwork\Modules\Project\Models\Component;
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
                'type' => 'ProjectTitleComponent',
                'deletable' => false,
                'component_id' => Component::where('type', 'ProjectTitleComponent')->first()->id
            ],
            [
                'name' => 'Actions',
                'order' => 2,
                'is_active' => true,
                'type' => 'ActionsComponent',
                'deletable' => false,
                'component_id' => null
            ],
        ];

        foreach ($dataSet as $data) {
            ProjectManagementBuilder::create($data);
        }
    }
}
