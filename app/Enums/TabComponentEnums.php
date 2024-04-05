<?php

namespace App\Enums;

enum TabComponentEnums: string
{
    // custom tab component types
    case CHECKBOX = 'Checkbox';
    case TEXT_FIELD = 'TextField';
    case DROPDOWN = 'DropDown';
    case TEXT_AREA = 'TextArea';
    case TITLE = 'Title';


    // default tab component types
    case CALENDAR = 'Calendar';
    case CHECKLIST = 'Checklist';
    case DOCUMENT = 'Document';
    case KEY_VISUAL = 'KeyVisual';
    case PROJECT_INFOS  = 'ProjectInfos';
    case PROJECT_STATUS = 'ProjectStatus';
    case PROJECT_TEAM = 'ProjectTeam';
    case PROJECT_BUDGET = 'ProjectBudget';
    case PROJECT_SHIFTS = 'ProjectShifts';
}
