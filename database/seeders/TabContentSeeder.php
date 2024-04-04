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
        /*
         * Create Components form this:
         *   case PROJECT_STATUS = 'ProjectStatusComponent';
    case PROJECT_GROUP = 'ProjectGroupComponent';
    case PROJECT_TEAM = 'ProjectTeamComponent';
    case PROJECT_ATTRIBUTES = 'ProjectAttributesComponent';
    case CALENDAR = 'CalendarTab';
    case CHECKLIST = 'ChecklistComponent';
    case SHIFT_TAB = 'ShiftTab';
    case RELEVANT_DATES_FOR_SHIFT_PLANNING = 'RelevantDatesForShiftPlanningComponent';
    case SHIFT_CONTACT_PERSONS = 'ShiftContactPersonsComponent';
    case GENERAL_SHIFT_INFORMATION = 'GeneralShiftInformationComponent';
    case BUDGET = 'BudgetComponent';
    case PROJECT_BUDGET_DEADLINE = 'ProjectBudgetDeadlineComponent';
    case COMMENT_TAB = 'CommentTab';
    case PROJECT_DOCUMENTS = 'ProjectDocumentsComponent';
    case PROJECT_TITLE = 'ProjectTitleComponent';
         */

        $components = [
            [
                'name' => 'Project Status',
                'type' => TabComponentEnums::PROJECT_STATUS,
                'data' => [],
                'special' => true
            ],
            [
                'name' => 'Project Group',
                'type' => TabComponentEnums::PROJECT_GROUP,
                'data' => [],
                'special' => true
            ],
            [
                'name' => 'Project Team',
                'type' => TabComponentEnums::PROJECT_TEAM,
                'data' => [],
                'special' => true
            ],
            [
                'name' => 'Project Attributes',
                'type' => TabComponentEnums::PROJECT_ATTRIBUTES,
                'data' => [],
                'special' => true
            ],
            [
                'name' => 'Calendar',
                'type' => TabComponentEnums::CALENDAR,
                'data' => [],
                'special' => true
            ],
            [
                'name' => 'Checklist',
                'type' => TabComponentEnums::CHECKLIST,
                'data' => [],
                'special' => true
            ],
            [
                'name' => 'Shift Tab',
                'type' => TabComponentEnums::SHIFT_TAB,
                'data' => [],
                'special' => true
            ],
            [
                'name' => 'Relevant Dates For Shift Planning',
                'type' => TabComponentEnums::RELEVANT_DATES_FOR_SHIFT_PLANNING,
                'data' => [],
                'special' => true
            ],
            [
                'name' => 'Shift Contact Persons',
                'type' => TabComponentEnums::SHIFT_CONTACT_PERSONS,
                'data' => [],
                'special' => true
            ],
            [
                'name' => 'General Shift Information',
                'type' => TabComponentEnums::GENERAL_SHIFT_INFORMATION,
                'data' => [],
                'special' => true
            ],
            [
                'name' => 'Budget',
                'type' => TabComponentEnums::BUDGET,
                'data' => [],
                'special' => true
            ],
            [
                'name' => 'Project Budget Deadline',
                'type' => TabComponentEnums::PROJECT_BUDGET_DEADLINE,
                'data' => [],
                'special' => true
            ],
            [
                'name' => 'Comment Tab',
                'type' => TabComponentEnums::COMMENT_TAB,
                'data' => [],
                'special' => true
            ],
            [
                'name' => 'Project Documents',
                'type' => TabComponentEnums::PROJECT_DOCUMENTS,
                'data' => [],
                'special' => true
            ],
            [
                'name' => 'Project Title',
                'type' => TabComponentEnums::PROJECT_TITLE,
                'data' => [],
                'special' => true
            ],
        ];

        foreach ($components as $component) {
            Component::create($component);
        }
    }
}
