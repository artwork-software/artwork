<?php

namespace Database\Seeders;

use Artwork\Modules\ProjectTab\Enums\ProjectTabComponentPermissionEnum;
use Artwork\Modules\ProjectTab\Enums\ProjectTabComponentEnum;
use Artwork\Modules\ProjectTab\Models\Component;
use Artwork\Modules\ProjectTab\Models\ProjectTab;
use Artwork\Modules\ProjectTab\Models\ProjectTabSidebarTab;
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
                'type' => ProjectTabComponentEnum::PROJECT_STATUS,
                'data' => [],
                'special' => true,
                'sidebar_enabled' => true,
                'permission_type' => ProjectTabComponentPermissionEnum::PERMISSION_TYPE_ALL_SEE_AND_EDIT->value
            ],
            [
                'name' => 'Project Group',
                'type' => ProjectTabComponentEnum::PROJECT_GROUP,
                'data' => [],
                'special' => true,
                'sidebar_enabled' => true,
                'permission_type' => ProjectTabComponentPermissionEnum::PERMISSION_TYPE_ALL_SEE_AND_EDIT->value
            ],
            [
                'name' => 'Project Team',
                'type' => ProjectTabComponentEnum::PROJECT_TEAM,
                'data' => [],
                'special' => true,
                'sidebar_enabled' => true,
                'permission_type' => ProjectTabComponentPermissionEnum::PERMISSION_TYPE_ALL_SEE_AND_EDIT->value
            ],
            [
                'name' => 'Project Attributes',
                'type' => ProjectTabComponentEnum::PROJECT_ATTRIBUTES,
                'data' => [],
                'special' => true,
                'sidebar_enabled' => true,
                'permission_type' => ProjectTabComponentPermissionEnum::PERMISSION_TYPE_ALL_SEE_AND_EDIT->value
            ],
            [
                'name' => 'Calendar',
                'type' => ProjectTabComponentEnum::CALENDAR,
                'data' => [],
                'special' => true,
                'sidebar_enabled' => false,
                'permission_type' => ProjectTabComponentPermissionEnum::PERMISSION_TYPE_ALL_SEE_AND_EDIT->value
            ],
            [
                'name' => 'Checklist',
                'type' => ProjectTabComponentEnum::CHECKLIST,
                'data' => [],
                'special' => true,
                'sidebar_enabled' => false,
                'permission_type' => ProjectTabComponentPermissionEnum::PERMISSION_TYPE_ALL_SEE_AND_EDIT->value
            ],
            [
                'name' => 'All Checklists',
                'type' => ProjectTabComponentEnum::CHECKLIST_ALL,
                'data' => [],
                'special' => true,
                'sidebar_enabled' => false
            ],
            [
                'name' => 'Shift Tab',
                'type' => ProjectTabComponentEnum::SHIFT_TAB,
                'data' => [],
                'special' => true,
                'sidebar_enabled' => false,
                'permission_type' => ProjectTabComponentPermissionEnum::PERMISSION_TYPE_ALL_SEE_AND_EDIT->value
            ],
            [
                'name' => 'Relevant Dates For Shift Planning',
                'type' => ProjectTabComponentEnum::RELEVANT_DATES_FOR_SHIFT_PLANNING,
                'data' => [],
                'special' => true,
                'sidebar_enabled' => true,
                'permission_type' => ProjectTabComponentPermissionEnum::PERMISSION_TYPE_ALL_SEE_AND_EDIT->value
            ],
            [
                'name' => 'Shift Contact Persons',
                'type' => ProjectTabComponentEnum::SHIFT_CONTACT_PERSONS,
                'data' => [],
                'special' => true,
                'sidebar_enabled' => true,
                'permission_type' => ProjectTabComponentPermissionEnum::PERMISSION_TYPE_ALL_SEE_AND_EDIT->value
            ],
            [
                'name' => 'General Shift Information',
                'type' => ProjectTabComponentEnum::GENERAL_SHIFT_INFORMATION,
                'data' => [],
                'special' => true,
                'sidebar_enabled' => true,
                'permission_type' => ProjectTabComponentPermissionEnum::PERMISSION_TYPE_ALL_SEE_AND_EDIT->value
            ],
            [
                'name' => 'Budget',
                'type' => ProjectTabComponentEnum::BUDGET,
                'data' => [],
                'special' => true,
                'sidebar_enabled' => false,
                'permission_type' => ProjectTabComponentPermissionEnum::PERMISSION_TYPE_ALL_SEE_AND_EDIT->value
            ],
            [
                'name' => 'Project Budget Deadline',
                'type' => ProjectTabComponentEnum::PROJECT_BUDGET_DEADLINE,
                'data' => [],
                'special' => true,
                'sidebar_enabled' => true,
                'permission_type' => ProjectTabComponentPermissionEnum::PERMISSION_TYPE_ALL_SEE_AND_EDIT->value
            ],
            [
                'name' => 'Comment Tab',
                'type' => ProjectTabComponentEnum::COMMENT_TAB,
                'data' => [],
                'special' => true,
                'sidebar_enabled' => false,
                'permission_type' => ProjectTabComponentPermissionEnum::PERMISSION_TYPE_ALL_SEE_AND_EDIT->value
            ],
            [
                'name' => 'All Comment Tab',
                'type' => ProjectTabComponentEnum::COMMENT_ALL_TAB,
                'data' => [],
                'special' => true,
                'sidebar_enabled' => false,
                'permission_type' => ProjectTabComponentPermissionEnum::PERMISSION_TYPE_ALL_SEE_AND_EDIT->value
            ],
            [
                'name' => 'Project Documents',
                'type' => ProjectTabComponentEnum::PROJECT_DOCUMENTS,
                'data' => [],
                'special' => true,
                'sidebar_enabled' => false,
                'permission_type' => ProjectTabComponentPermissionEnum::PERMISSION_TYPE_ALL_SEE_AND_EDIT->value
            ],
            [
                'name' => 'All Project Documents',
                'type' => ProjectTabComponentEnum::PROJECT_ALL_DOCUMENTS,
                'data' => [],
                'special' => true,
                'sidebar_enabled' => false,
                'permission_type' => ProjectTabComponentPermissionEnum::PERMISSION_TYPE_ALL_SEE_AND_EDIT->value
            ],
            [
                'name' => 'Project Title',
                'type' => ProjectTabComponentEnum::PROJECT_TITLE,
                'data' => [],
                'special' => true,
                'sidebar_enabled' => true,
                'permission_type' => ProjectTabComponentPermissionEnum::PERMISSION_TYPE_ALL_SEE_AND_EDIT->value
            ],
            [
                'name' => 'Separator 10 Pixel',
                'type' => ProjectTabComponentEnum::SEPARATOR,
                'data' => [
                    'height' => '10',
                    'showLine' => true
                ],
                'special' => false,
                'sidebar_enabled' => true,
                'permission_type' => ProjectTabComponentPermissionEnum::PERMISSION_TYPE_ALL_SEE_AND_EDIT->value
            ],
            [
                'name' => 'Budgetinformationen',
                'type' => ProjectTabComponentEnum::BUDGET_INFORMATIONS,
                'data' => [],
                'special' => true,
                'sidebar_enabled' => true,
                'permission_type' => ProjectTabComponentPermissionEnum::PERMISSION_TYPE_ALL_SEE_AND_EDIT->value
            ]
        ];

        foreach ($components as $component) {
            Component::create($component);
        }

        $this->createInformationSidebar($this->createProjectInformationTab());
        $this->createInformationSidebar($this->createScheduleTab());
        $this->createInformationSidebar($this->createChecklistsTab());
        $this->createShiftsSidebar($this->createShiftsTab());
        $this->createBudgetSidebar($this->createBudgetTab());
        $this->createInformationSidebar($this->createCommentsTab());
    }

    private function createInformationSidebar(ProjectTab $projectTab): void
    {
        /** @var ProjectTabSidebarTab $projectInformationSidebarTab */
        $projectInformationSidebarTab = $projectTab->sidebarTabs()->create([
            'name' => 'Projektinformationen',
            'order' => 1
        ]);
        $projectInformationSidebarTab->componentsInSidebar()->create([
            'component_id' => Component::query()
                ->where('type', ProjectTabComponentEnum::PROJECT_TEAM->value)
                ->first()
                ->id,
            'order' => 1,
        ]);
        $projectInformationSidebarTab->componentsInSidebar()->create([
            'component_id' => Component::query()
                ->where('type', ProjectTabComponentEnum::SEPARATOR->value)
                ->first()
                ->id,
            'order' => 2,
        ]);
        $projectInformationSidebarTab->componentsInSidebar()->create([
            'component_id' => Component::query()
                ->where('type', ProjectTabComponentEnum::PROJECT_ATTRIBUTES->value)
                ->first()
                ->id,
            'order' => 3,
        ]);
    }

    private function createShiftsSidebar(ProjectTab $projectTab): void
    {
        /** @var ProjectTabSidebarTab $projectShiftsSidebarTab */
        $projectShiftsSidebarTab = $projectTab->sidebarTabs()->create([
            'name' => 'Schichtinformationen',
            'order' => 1
        ]);
        $projectShiftsSidebarTab->componentsInSidebar()->create([
            'component_id' => Component::query()
                ->where('type', ProjectTabComponentEnum::RELEVANT_DATES_FOR_SHIFT_PLANNING->value)
                ->first()
                ->id,
            'order' => 1,
        ]);
        $projectShiftsSidebarTab->componentsInSidebar()->create([
            'component_id' => Component::query()
                ->where('type', ProjectTabComponentEnum::SEPARATOR->value)
                ->first()
                ->id,
            'order' => 2,
        ]);
        $projectShiftsSidebarTab->componentsInSidebar()->create([
            'component_id' => Component::query()
                ->where('type', ProjectTabComponentEnum::SHIFT_CONTACT_PERSONS->value)
                ->first()
                ->id,
            'order' => 3,
        ]);
        $projectShiftsSidebarTab->componentsInSidebar()->create([
            'component_id' => Component::query()
                ->where('type', ProjectTabComponentEnum::SEPARATOR->value)
                ->first()
                ->id,
            'order' => 4,
        ]);
        $projectShiftsSidebarTab->componentsInSidebar()->create([
            'component_id' => Component::query()
                ->where('type', ProjectTabComponentEnum::GENERAL_SHIFT_INFORMATION->value)
                ->first()
                ->id,
            'order' => 5,
        ]);
    }

    private function createBudgetSidebar(ProjectTab $projectTab): void
    {
        /** @var ProjectTabSidebarTab $budgetInformationSidebarTab */
        $budgetInformationSidebarTab = $projectTab->sidebarTabs()->create([
            'name' => 'Budgetinformationen',
            'order' => 1
        ]);
        $budgetInformationSidebarTab->componentsInSidebar()->create([
            'component_id' => Component::query()
                ->where('type', ProjectTabComponentEnum::BUDGET_INFORMATIONS)
                ->first()
                ->id,
            'order' => 1,
        ]);
    }

    private function createProjectInformationTab(): ProjectTab
    {
        /** @var ProjectTab $projectInformationTab */
        $projectInformationTab = ProjectTab::create([
            'name' => 'Project Information',
            'order' => 1
        ]);

        $shortDescriptionLabel = Component::create([
            'name' => 'Short Description',
            'type' => ProjectTabComponentEnum::TITLE,
            'data' => [
                'title' => 'Short Description',
                'title_size' => '15'
            ],
            'special' => false,
            'sidebar_enabled' => true,
            'permission_type' => ProjectTabComponentPermissionEnum::PERMISSION_TYPE_ALL_SEE_AND_EDIT
        ]);

        $projectInformationTab->components()->create([
            'component_id' => $shortDescriptionLabel->id,
            'order' => 1,
            'scope' => []
        ]);

        /** @var Component $shortDescriptionComponent */
        $shortDescriptionComponent = Component::create([
            'name' => 'Short Description',
            'type' => ProjectTabComponentEnum::TEXT_AREA,
            'data' => [
                'label' => '',
                'text' => '',
                'placeholder' => 'Short Description'
            ],
            'special' => false,
            'sidebar_enabled' => true,
            'permission_type' => ProjectTabComponentPermissionEnum::PERMISSION_TYPE_ALL_SEE_AND_EDIT
        ]);

        $projectInformationTab->components()->create([
            'component_id' => $shortDescriptionComponent->id,
            'order' => 2,
            'scope' => []
        ]);

        $websiteTextTitleComponent = Component::create([
            'name' => 'Website-Text',
            'type' => ProjectTabComponentEnum::TITLE,
            'data' => [
                'title' => 'Website-Text',
                'title_size' => '15'
            ],
            'special' => false,
            'sidebar_enabled' => true,
            'permission_type' => ProjectTabComponentPermissionEnum::PERMISSION_TYPE_ALL_SEE_AND_EDIT
        ]);

        $projectInformationTab->components()->create([
            'component_id' => $websiteTextTitleComponent->id,
            'order' => 3,
            'scope' => []
        ]);

        $websiteTextComponent = Component::create([
            'name' => 'Website-Text',
            'type' => ProjectTabComponentEnum::TEXT_AREA,
            'data' => [
                'label' => '',
                'text' => '',
                'placeholder' => 'Website-Text'
            ],
            'special' => false,
            'sidebar_enabled' => true,
            'permission_type' => ProjectTabComponentPermissionEnum::PERMISSION_TYPE_ALL_SEE_AND_EDIT
        ]);

        $projectInformationTab->components()->create([
            'component_id' => $websiteTextComponent->id,
            'order' => 4,
            'scope' => []
        ]);

        $oeaTitleComponent = Component::create([
            'name' => 'ÖA',
            'type' => ProjectTabComponentEnum::TITLE,
            'data' => [
                'title' => 'ÖA',
                'title_size' => '15'
            ],
            'special' => false,
            'sidebar_enabled' => true,
            'permission_type' => ProjectTabComponentPermissionEnum::PERMISSION_TYPE_ALL_SEE_AND_EDIT
        ]);

        $projectInformationTab->components()->create([
            'component_id' => $oeaTitleComponent->id,
            'order' => 5,
            'scope' => []
        ]);

        $oeaComponent = Component::create([
            'name' => 'ÖA',
            'type' => ProjectTabComponentEnum::TEXT_AREA,
            'data' => [
                'label' => '',
                'text' => '',
                'placeholder' => 'ÖA'
            ],
            'special' => false,
            'sidebar_enabled' => true,
            'permission_type' => ProjectTabComponentPermissionEnum::PERMISSION_TYPE_ALL_SEE_AND_EDIT
        ]);

        $projectInformationTab->components()->create([
            'component_id' => $oeaComponent->id,
            'order' => 6,
            'scope' => []
        ]);

        $projectInformationTab->components()->create([
            'component_id' => Component::query()
                ->where('type', ProjectTabComponentEnum::PROJECT_DOCUMENTS)
                ->first()
                ?->id,
            'order' => 7,
            'scope' => [$projectInformationTab->id]
        ]);

        return $projectInformationTab;
    }

    private function createScheduleTab(): ProjectTab
    {
        /** @var ProjectTab $scheduleTab */
        $scheduleTab = ProjectTab::create([
            'name' => 'Schedule',
            'order' => 2
        ]);

        $scheduleTab->components()->create([
            'component_id' => Component::query()->where('name', 'Calendar')->first()->id,
            'order' => 1,
            'scope' => []
        ]);

        return $scheduleTab;
    }

    private function createChecklistsTab(): ProjectTab
    {
        /** @var ProjectTab $checklistsTab */
        $checklistsTab = ProjectTab::create([
            'name' => 'Checklists',
            'order' => 3
        ]);

        $checklistsTab->components()->create([
            'component_id' => Component::query()->where('name', 'Checklist')->first()->id,
            'order' => 1,
            'scope' => [$checklistsTab->id]
        ]);

        return $checklistsTab;
    }

    private function createShiftsTab(): ProjectTab
    {
        /** @var ProjectTab $shiftsTab */
        $shiftsTab = ProjectTab::create([
            'name' => 'Shifts',
            'order' => 4
        ]);

        $shiftsTab->components()->create([
            'component_id' => Component::query()->where('name', 'Shift Tab')->first()->id,
            'order' => 1,
            'scope' => []
        ]);

        return $shiftsTab;
    }

    private function createBudgetTab(): ProjectTab
    {
        /** @var ProjectTab $budgetTab */
        $budgetTab = ProjectTab::create([
            'name' => 'Budget',
            'order' => 5
        ]);

        $budgetTab->components()->create([
            'component_id' => Component::query()->where('name', 'Budget')->first()->id,
            'order' => 1,
            'scope' => []
        ]);

        return $budgetTab;
    }

    private function createCommentsTab(): ProjectTab
    {
        /** @var ProjectTab $commentsTab */
        $commentsTab = ProjectTab::create([
            'name' => 'Comments',
            'order' => 6
        ]);

        $commentsTab->components()->create([
            'component_id' => Component::query()->where('name', 'Comment Tab')->first()->id,
            'order' => 1,
            'scope' => [$commentsTab->id]
        ]);

        return $commentsTab;
    }
}
