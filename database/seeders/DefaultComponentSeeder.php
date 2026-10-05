<?php

namespace Database\Seeders;

use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Enum\ProjectTabComponentPermissionEnum;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\TabTemplates\ProjectTabTemplateCatalog;
use Artwork\Modules\Project\TabTemplates\ProjectTabTemplateService;
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
                'name' => 'Project period',
                'type' => ProjectTabComponentEnum::PROJECT_PERIOD,
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
                'name' => 'Budget Informations',
                'type' => ProjectTabComponentEnum::BUDGET_INFORMATIONS,
                'data' => [],
                'special' => true,
                'sidebar_enabled' => true,
                'permission_type' => ProjectTabComponentPermissionEnum::PERMISSION_TYPE_ALL_SEE_AND_EDIT->value
            ],
            [
                'name' => 'Project group display component',
                'type' => ProjectTabComponentEnum::PROJECT_GROUP_DISPLAY,
                'data' => [],
                'special' => false,
                'sidebar_enabled' => true,
                'permission_type' => ProjectTabComponentPermissionEnum::PERMISSION_TYPE_ALL_SEE_AND_EDIT->value
            ],
            [
                'name' => 'Component Subprojects',
                'type' => ProjectTabComponentEnum::GROUP_PROJECT_DISPLAY,
                'data' => [],
                'special' => true,
                'sidebar_enabled' => true,
                'permission_type' => ProjectTabComponentPermissionEnum::PERMISSION_TYPE_ALL_SEE_AND_EDIT->value
            ],
            [
                'name' => 'Artist Name Display Component',
                'type' => ProjectTabComponentEnum::ARTIST_NAME_DISPLAY,
                'data' => [],
                'special' => true,
                'sidebar_enabled' => true,
                'permission_type' => ProjectTabComponentPermissionEnum::PERMISSION_TYPE_ALL_SEE_AND_EDIT->value
            ]
        ];

        foreach ($components as $component) {
            Component::create($component);
        }

        if (!Component::query()->where('type', ProjectTabComponentEnum::BULK_EDIT)->first()) {
            Component::create([
                'name' => 'Bulk Event Create',
                'type' => ProjectTabComponentEnum::BULK_EDIT,
                'data' => [],
                'special' => true,
                'sidebar_enabled' => false,
                'permission_type' => ProjectTabComponentPermissionEnum::PERMISSION_TYPE_ALL_SEE_AND_EDIT->value
            ]);
        }

        if (!Component::query()->where('type', ProjectTabComponentEnum::ARTIST_RESIDENCIES)->first()) {
            Component::create([
                'name' => 'Artist residencies',
                'type' => ProjectTabComponentEnum::ARTIST_RESIDENCIES,
                'data' => [],
                'special' => true,
                'sidebar_enabled' => false,
                'permission_type' => ProjectTabComponentPermissionEnum::PERMISSION_TYPE_ALL_SEE_AND_EDIT->value
            ]);
        }

        // Standard-Tabs aus den Systemvorlagen (dieselben, die in den Tab-Einstellungen als Vorlage
        // zur Verfügung stehen): gegliedert mit Abschnittsbalken, Hinweisen und Seitenleisten.
        $templateService = app(ProjectTabTemplateService::class);
        foreach (ProjectTabTemplateCatalog::STANDARD_TABS as $templateKey) {
            $templateService->apply($templateKey);
        }
    }
}
