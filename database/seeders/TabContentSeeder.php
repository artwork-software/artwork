<?php

namespace Database\Seeders;

use App\Enums\TabComponentEnums;
use Artwork\Modules\ProjectTab\Models\Component;
use Artwork\Modules\ProjectTab\Models\ProjectTab;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TabContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $components = [
            [
                'name' => 'Project Status',
                'type' => TabComponentEnums::PROJECT_STATUS,
                'data' => [],
                'special' => true,
                'sidebar_enabled' => true
            ],
            [
                'name' => 'Project Group',
                'type' => TabComponentEnums::PROJECT_GROUP,
                'data' => [],
                'special' => true,
                'sidebar_enabled' => true
            ],
            [
                'name' => 'Project Team',
                'type' => TabComponentEnums::PROJECT_TEAM,
                'data' => [],
                'special' => true,
                'sidebar_enabled' => true
            ],
            [
                'name' => 'Project Attributes',
                'type' => TabComponentEnums::PROJECT_ATTRIBUTES,
                'data' => [],
                'special' => true,
                'sidebar_enabled' => true
            ],
            [
                'name' => 'Calendar',
                'type' => TabComponentEnums::CALENDAR,
                'data' => [],
                'special' => true,
                'sidebar_enabled' => false
            ],
            [
                'name' => 'Checklist',
                'type' => TabComponentEnums::CHECKLIST,
                'data' => [],
                'special' => true,
                'sidebar_enabled' => false
            ],
            [
                'name' => 'All Checklists',
                'type' => TabComponentEnums::CHECKLIST_ALL,
                'data' => [],
                'special' => true,
                'sidebar_enabled' => false
            ],
            [
                'name' => 'Shift Tab',
                'type' => TabComponentEnums::SHIFT_TAB,
                'data' => [],
                'special' => true,
                'sidebar_enabled' => false
            ],
            [
                'name' => 'Relevant Dates For Shift Planning',
                'type' => TabComponentEnums::RELEVANT_DATES_FOR_SHIFT_PLANNING,
                'data' => [],
                'special' => true,
                'sidebar_enabled' => true
            ],
            [
                'name' => 'Shift Contact Persons',
                'type' => TabComponentEnums::SHIFT_CONTACT_PERSONS,
                'data' => [],
                'special' => true,
                'sidebar_enabled' => true
            ],
            [
                'name' => 'General Shift Information',
                'type' => TabComponentEnums::GENERAL_SHIFT_INFORMATION,
                'data' => [],
                'special' => true,
                'sidebar_enabled' => true
            ],
            [
                'name' => 'Budget',
                'type' => TabComponentEnums::BUDGET,
                'data' => [],
                'special' => true,
                'sidebar_enabled' => false
            ],
            [
                'name' => 'Project Budget Deadline',
                'type' => TabComponentEnums::PROJECT_BUDGET_DEADLINE,
                'data' => [],
                'special' => true,
                'sidebar_enabled' => true
            ],
            [
                'name' => 'Comment Tab',
                'type' => TabComponentEnums::COMMENT_TAB,
                'data' => [],
                'special' => true,
                'sidebar_enabled' => false
            ],
            [
                'name' => 'All Comment Tab',
                'type' => TabComponentEnums::COMMENT_ALL_TAB,
                'data' => [],
                'special' => true,
                'sidebar_enabled' => false
            ],
            [
                'name' => 'Project Documents',
                'type' => TabComponentEnums::PROJECT_DOCUMENTS,
                'data' => [],
                'special' => true,
                'sidebar_enabled' => false
            ],
            [
                'name' => 'All Project Documents',
                'type' => TabComponentEnums::PROJECT_ALL_DOCUMENTS,
                'data' => [],
                'special' => true,
                'sidebar_enabled' => false
            ],
            [
                'name' => 'Project Title',
                'type' => TabComponentEnums::PROJECT_TITLE,
                'data' => [],
                'special' => true,
                'sidebar_enabled' => true
            ],
            [
                'name' => 'Separator 10 Pixel',
                'type' => TabComponentEnums::SEPARATOR,
                'data' => [
                    'height' => '10',
                    'showLine' => true
                ],
                'special' => false,
                'sidebar_enabled' => true
            ]
        ];


        $tabs = [
            [
                'name' => 'Project Information',
                'order' => 1
            ],
            [
                'name' => 'Project Team',
                'order' => 2
            ],
            [
                'name' => 'Shift Planning',
                'order' => 3
            ],
            [
                'name' => 'Budget',
                'order' => 4
            ],
            [
                'name' => 'Documents',
                'order' => 5
            ],
            [
                'name' => 'Comments',
                'order' => 6
            ]
        ];

        foreach ($components as $component) {
            Component::create($component);
        }

        foreach ($tabs as $tab) {
            ProjectTab::create($tab);
        }
    }
}
