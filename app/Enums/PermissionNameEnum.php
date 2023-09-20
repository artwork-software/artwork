<?php

namespace App\Enums;

enum PermissionNameEnum : string
{
    // New Permissions


    // Projects
    case PROJECT_MANAGEMENT = 'management projects';
    case PROJECT_DELETE = 'delete projects';
    case PROJECT_VIEW = 'view projects';
    case ADD_EDIT_OWN_PROJECT = 'create and edit own project';
    case WRITE_PROJECTS = 'write projects';
    case PROJECT_BUDGET_ADMIN = 'access project budgets';

    case PROJECT_BUDGET_VERIFIED_ADD_REMOVE = 'can add and remove verified states';
    case PROJECT_BUDGET_SEE_DOCS_CONTRACTS = 'can see, edit and delete project contracts and docs';

    // ROOM
    case EVENT_REQUEST = 'request room occupancy';
    case ROOM_REQUEST_READING_DETAILS = 'read details room request';
    //case ROOM_REQUEST_CONFIRM = 'confirm prioritize edit room requests';
    case ROOM_UPDATE = 'create, delete and update rooms';

    // Docs & Budget
    case CONTRACT_EDIT_UPLOAD = 'view edit upload contracts';
    case MONEY_SOURCE_EDIT_VIEW_ADD = 'view edit add money_sources';
    case CONTRACT_SEE_DOWNLOAD = 'can see and download contract modules';
    case MONEY_SOURCE_EDIT_DELETE = 'can edit and delete money sources';

    // System
    case USER_UPDATE = 'usermanagement';
    case CHECKLIST_SETTINGS_ADMIN = 'admin checklistTemplates';
    case TEAM_UPDATE = 'teammanagement';
    case DEPARTMENT_UPDATE = 'update departments';
    case ROOM_ADMIN = 'admin rooms';
    case SETTINGS_UPDATE = 'change tool settings';
    case PROJECT_SETTINGS_UPDATE = 'change project settings';
    case EVENT_SETTINGS_UPDATE = 'change event settings';

    case SYSTEM_NOTIFICATION = 'change system notification';

    case VIEW_BUDGET_TEMPLATES = 'view budget templates';

    case UPDATE_BUDGET_TEMPLATES = 'edit budget templates';

    // deleted
    //case PROJECT_DELETE = 'delete projects';
    //case EVENT_TYPE_SETTINGS_ADMIN = 'admin eventTypeSettings';
    //case PROJECT_ADMIN = 'admin projects';
    case CHECKLIST_UPDATE = 'update checklists';

    //case CHECKLIST_VIEW = 'view checklists';
    //case PROJECT_SETTINGS_ADMIN = 'admin projectSettings';
    //case CHECKLIST_DELETE = 'delete checklists';
    //case GLOBAL_NOTIFICATION_ADMIN = 'admin globalNotification';
    case PROJECT_UPDATE = 'create and edit projects';

    // NEW PERMISSIONS JUNE'23
    case MA_MANAGER = 'can manage workers';
    case SHIFT_PLANNER = 'can plan shifts';

    case GLOBAL_DOCUMENT_ADMIN = 'can manage global documents';

    case GLOBAL_PROJECT_BUDGET_ADMIN = 'can manage global project budgets';

    case VIEW_SHIFT_PLAN = 'can view shift plan';

    case CAN_COMMIT_SHIFTS = 'can commit shifts';

    case EDIT_EXTERNAL_USERS_CONDITIONS = 'can edit external users conditions';
}
