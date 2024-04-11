<?php

namespace Database\Seeders;

use App\Enums\ComponentPermissionNameEnum;
use App\Enums\TabComponentEnums;
use Artwork\Modules\ProjectTab\Models\Component;
use Artwork\Modules\ProjectTab\Models\ProjectTab;
use Illuminate\Database\Seeder;

class DefaultComponentSeeder extends Seeder
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
                'sidebar_enabled' => true,
                'permission_type' => ComponentPermissionNameEnum::PERMISSION_TYPE_ALL_SEE_AND_EDIT->value
            ],
            [
                'name' => 'Project Group',
                'type' => TabComponentEnums::PROJECT_GROUP,
                'data' => [],
                'special' => true,
                'sidebar_enabled' => true,
                'permission_type' => ComponentPermissionNameEnum::PERMISSION_TYPE_ALL_SEE_AND_EDIT->value
            ],
            [
                'name' => 'Project Team',
                'type' => TabComponentEnums::PROJECT_TEAM,
                'data' => [],
                'special' => true,
                'sidebar_enabled' => true,
                'permission_type' => ComponentPermissionNameEnum::PERMISSION_TYPE_ALL_SEE_AND_EDIT->value
            ],
            [
                'name' => 'Project Attributes',
                'type' => TabComponentEnums::PROJECT_ATTRIBUTES,
                'data' => [],
                'special' => true,
                'sidebar_enabled' => true,
                'permission_type' => ComponentPermissionNameEnum::PERMISSION_TYPE_ALL_SEE_AND_EDIT->value
            ],
            [
                'name' => 'Calendar',
                'type' => TabComponentEnums::CALENDAR,
                'data' => [],
                'special' => true,
                'sidebar_enabled' => false,
                'permission_type' => ComponentPermissionNameEnum::PERMISSION_TYPE_ALL_SEE_AND_EDIT->value
            ],
            [
                'name' => 'Checklist',
                'type' => TabComponentEnums::CHECKLIST,
                'data' => [],
                'special' => true,
                'sidebar_enabled' => false,
                'permission_type' => ComponentPermissionNameEnum::PERMISSION_TYPE_ALL_SEE_AND_EDIT->value
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
                'sidebar_enabled' => false,
                'permission_type' => ComponentPermissionNameEnum::PERMISSION_TYPE_ALL_SEE_AND_EDIT->value
            ],
            [
                'name' => 'Relevant Dates For Shift Planning',
                'type' => TabComponentEnums::RELEVANT_DATES_FOR_SHIFT_PLANNING,
                'data' => [],
                'special' => true,
                'sidebar_enabled' => true,
                'permission_type' => ComponentPermissionNameEnum::PERMISSION_TYPE_ALL_SEE_AND_EDIT->value
            ],
            [
                'name' => 'Shift Contact Persons',
                'type' => TabComponentEnums::SHIFT_CONTACT_PERSONS,
                'data' => [],
                'special' => true,
                'sidebar_enabled' => true,
                'permission_type' => ComponentPermissionNameEnum::PERMISSION_TYPE_ALL_SEE_AND_EDIT->value
            ],
            [
                'name' => 'General Shift Information',
                'type' => TabComponentEnums::GENERAL_SHIFT_INFORMATION,
                'data' => [],
                'special' => true,
                'sidebar_enabled' => true,
                'permission_type' => ComponentPermissionNameEnum::PERMISSION_TYPE_ALL_SEE_AND_EDIT->value
            ],
            [
                'name' => 'Budget',
                'type' => TabComponentEnums::BUDGET,
                'data' => [],
                'special' => true,
                'sidebar_enabled' => false,
                'permission_type' => ComponentPermissionNameEnum::PERMISSION_TYPE_ALL_SEE_AND_EDIT->value
            ],
            [
                'name' => 'Project Budget Deadline',
                'type' => TabComponentEnums::PROJECT_BUDGET_DEADLINE,
                'data' => [],
                'special' => true,
                'sidebar_enabled' => true,
                'permission_type' => ComponentPermissionNameEnum::PERMISSION_TYPE_ALL_SEE_AND_EDIT->value
            ],
            [
                'name' => 'Comment Tab',
                'type' => TabComponentEnums::COMMENT_TAB,
                'data' => [],
                'special' => true,
                'sidebar_enabled' => false,
                'permission_type' => ComponentPermissionNameEnum::PERMISSION_TYPE_ALL_SEE_AND_EDIT->value
            ],
            [
                'name' => 'All Comment Tab',
                'type' => TabComponentEnums::COMMENT_ALL_TAB,
                'data' => [],
                'special' => true,
                'sidebar_enabled' => false,
                'permission_type' => ComponentPermissionNameEnum::PERMISSION_TYPE_ALL_SEE_AND_EDIT->value
            ],
            [
                'name' => 'Project Documents',
                'type' => TabComponentEnums::PROJECT_DOCUMENTS,
                'data' => [],
                'special' => true,
                'sidebar_enabled' => false,
                'permission_type' => ComponentPermissionNameEnum::PERMISSION_TYPE_ALL_SEE_AND_EDIT->value
            ],
            [
                'name' => 'All Project Documents',
                'type' => TabComponentEnums::PROJECT_ALL_DOCUMENTS,
                'data' => [],
                'special' => true,
                'sidebar_enabled' => false,
                'permission_type' => ComponentPermissionNameEnum::PERMISSION_TYPE_ALL_SEE_AND_EDIT->value
            ],
            [
                'name' => 'Project Title',
                'type' => TabComponentEnums::PROJECT_TITLE,
                'data' => [],
                'special' => true,
                'sidebar_enabled' => true,
                'permission_type' => ComponentPermissionNameEnum::PERMISSION_TYPE_ALL_SEE_AND_EDIT->value
            ],
            [
                'name' => 'Separator 10 Pixel',
                'type' => TabComponentEnums::SEPARATOR,
                'data' => [
                    'height' => '10',
                    'showLine' => true
                ],
                'special' => false,
                'sidebar_enabled' => true,
                'permission_type' => ComponentPermissionNameEnum::PERMISSION_TYPE_ALL_SEE_AND_EDIT->value
            ]
        ];

        foreach ($components as $component) {
            Component::create($component);
        }

        $this->createProjectInformationTab();
        $this->createScheduleTab();
        $this->createChecklistsTab();
        $this->createShiftsTab();
        $this->createBudgetTab();
        $this->createCommentsTab();
    }

    private function createProjectInformationTab(): void
    {
        /** @var ProjectTab $projectInformationTab */
        $projectInformationTab = ProjectTab::create([
            'name' => 'Project Information',
            'order' => 1
        ]);

        $shortDescriptionLabel = Component::create([
            'name' => 'Short Description',
            'type' => TabComponentEnums::TITLE,
            'data' => [
                'title' => 'Short Description'
            ],
            'special' => false,
            'sidebar_enabled' => true,
            'permission_type' => ComponentPermissionNameEnum::PERMISSION_TYPE_ALL_SEE_AND_EDIT
        ]);

        $projectInformationTab->components()->create([
            'component_id' => $shortDescriptionLabel->id,
            'order' => 1,
        ]);

        /** @var Component $shortDescriptionComponent */
        $shortDescriptionComponent = Component::create([
            'name' => 'Short Description',
            'type' => TabComponentEnums::TEXT_AREA,
            'data' => [
                'label' => '',
                'text' => '',
                'placeholder' => 'Short Description'
            ],
            'special' => false,
            'sidebar_enabled' => true,
            'permission_type' => ComponentPermissionNameEnum::PERMISSION_TYPE_ALL_SEE_AND_EDIT
        ]);

        $projectInformationTab->components()->create([
            'component_id' => $shortDescriptionComponent->id,
            'order' => 2,
        ]);

        $websiteTextTitleComponent = Component::create([
            'name' => 'Website-Text',
            'type' => TabComponentEnums::TITLE,
            'data' => [
                'title' => 'Website-Text',
            ],
            'special' => false,
            'sidebar_enabled' => true,
            'permission_type' => ComponentPermissionNameEnum::PERMISSION_TYPE_ALL_SEE_AND_EDIT
        ]);

        $projectInformationTab->components()->create([
            'component_id' => $websiteTextTitleComponent->id,
            'order' => 3,
        ]);

        $websiteTextComponent = Component::create([
            'name' => 'Website-Text',
            'type' => TabComponentEnums::TEXT_AREA,
            'data' => [
                'label' => '',
                'text' => '',
                'placeholder' => 'Website-Text'
            ],
            'special' => false,
            'sidebar_enabled' => true,
            'permission_type' => ComponentPermissionNameEnum::PERMISSION_TYPE_ALL_SEE_AND_EDIT
        ]);

        $projectInformationTab->components()->create([
            'component_id' => $websiteTextComponent->id,
            'order' => 4,
        ]);

        $oeaTitleComponent = Component::create([
            'name' => 'ÖA',
            'type' => TabComponentEnums::TITLE,
            'data' => [
                'title' => 'ÖA',
            ],
            'special' => false,
            'sidebar_enabled' => true,
            'permission_type' => ComponentPermissionNameEnum::PERMISSION_TYPE_ALL_SEE_AND_EDIT
        ]);

        $projectInformationTab->components()->create([
            'component_id' => $oeaTitleComponent->id,
            'order' => 5,
        ]);

        $oeaComponent = Component::create([
            'name' => 'ÖA',
            'type' => TabComponentEnums::TEXT_AREA,
            'data' => [
                'label' => '',
                'text' => '',
                'placeholder' => 'ÖA'
            ],
            'special' => false,
            'sidebar_enabled' => true,
            'permission_type' => ComponentPermissionNameEnum::PERMISSION_TYPE_ALL_SEE_AND_EDIT
        ]);

        $projectInformationTab->components()->create([
            'component_id' => $oeaComponent->id,
            'order' => 6,
        ]);

        $documentsComponent = Component::create([
            'name' => 'Project-Documents',
            'type' => TabComponentEnums::PROJECT_DOCUMENTS,
            'data' => [],
            'special' => false,
            'sidebar_enabled' => true,
            'permission_type' => ComponentPermissionNameEnum::PERMISSION_TYPE_ALL_SEE_AND_EDIT
        ]);

        $projectInformationTab->components()->create([
            'component_id' => $documentsComponent->id,
            'order' => 7,
        ]);
    }

    private function createScheduleTab(): void
    {
        /** @var ProjectTab $scheduleTab */
        $scheduleTab = ProjectTab::create([
            'name' => 'Schedule',
            'order' => 2
        ]);

        $scheduleTab->components()->create([
            'component_id' => Component::query()->where('name', 'Calendar')->first()->id,
            'order' => 1,
        ]);
    }

    private function createChecklistsTab(): void
    {
        /** @var ProjectTab $checklistsTab */
        $checklistsTab = ProjectTab::create([
            'name' => 'Checklists',
            'order' => 3
        ]);

        $checklistsTab->components()->create([
            'component_id' => Component::query()->where('name', 'Checklist')->first()->id,
            'order' => 1,
        ]);
    }

    private function createShiftsTab(): void
    {
        /** @var ProjectTab $shiftsTab */
        $shiftsTab = ProjectTab::create([
            'name' => 'Shifts',
            'order' => 4
        ]);

        $shiftsTab->components()->create([
            'component_id' => Component::query()->where('name', 'Shift Tab')->first()->id,
            'order' => 1,
        ]);
    }

    private function createBudgetTab(): void
    {
        /** @var ProjectTab $budgetTab */
        $budgetTab = ProjectTab::create([
            'name' => 'Budget',
            'order' => 5
        ]);

        $budgetTab->components()->create([
            'component_id' => Component::query()->where('name', 'Budget')->first()->id,
            'order' => 1,
        ]);
    }

    private function createCommentsTab(): void
    {
        /** @var ProjectTab $commentsTab */
        $commentsTab = ProjectTab::create([
            'name' => 'Comments',
            'order' => 6
        ]);

        $commentsTab->components()->create([
            'component_id' => Component::query()->where('name', 'Comment Tab')->first()->id,
            'order' => 1,
        ]);
    }
}
