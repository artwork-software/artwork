<?php

namespace Artwork\Modules\Permission\Enums;

enum PermissionEnum : string
{
    case PROJECT_MANAGEMENT = 'management projects';
    case PROJECT_DELETE = 'delete projects';
    case PROJECT_VIEW = 'view projects';
    case ADD_EDIT_OWN_PROJECT = 'create and edit own project';
    case WRITE_PROJECTS = 'write projects';
    case PROJECT_BUDGET_VERIFIED_ADD_REMOVE = 'can add and remove verified states';
    case PROJECT_BUDGET_SEE_DOCS_CONTRACTS = 'can see, edit and delete project contracts and docs';
    case EVENT_REQUEST = 'request room occupancy';
    case ROOM_UPDATE = 'create, delete and update rooms';
    case CONTRACT_EDIT_UPLOAD = 'view edit upload contracts';
    case MONEY_SOURCE_EDIT_VIEW_ADD = 'view edit add money_sources';
    case CONTRACT_SEE_DOWNLOAD = 'can see and download contract modules';
    case MONEY_SOURCE_EDIT_DELETE = 'can edit and delete money sources';
    case CHECKLIST_SETTINGS_ADMIN = 'admin checklistTemplates';
    case TEAM_UPDATE = 'teammanagement';
    case SETTINGS_UPDATE = 'change tool settings';
    case PROJECT_SETTINGS_UPDATE = 'change project settings';
    case EVENT_SETTINGS_UPDATE = 'change event settings';
    case SYSTEM_NOTIFICATION = 'change system notification';
    case VIEW_BUDGET_TEMPLATES = 'view budget templates';
    case UPDATE_BUDGET_TEMPLATES = 'edit budget templates';
    case MA_MANAGER = 'can manage workers';
    case SHIFT_PLANNER = 'can plan shifts';
    case GLOBAL_PROJECT_BUDGET_ADMIN = 'can manage global project budgets';
    case GLOBAL_PROJECT_BUDGET_ADMIN_NO_DOCS = 'can manage all project budgets without docs';
    case VIEW_SHIFT_PLAN = 'can view shift plan';
    case CAN_COMMIT_SHIFTS = 'can commit shifts';
    case EDIT_EXTERNAL_USERS_CONDITIONS = 'can edit external users conditions';

    case VIEW_AND_DELETE_SAGE100_API_DATA = 'can view and delete sage100-api-data';
}
