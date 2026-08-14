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
    case CREATE_EVENTS_WITHOUT_REQUEST = 'create events without request';
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

    case VIEW_PROJECT_SAGE_DATA = 'can view project sage data';
    case VIEW_GLOBAL_SAGE_DATA = 'can view global sage data';

    case CHECKLIST_USE_PERMISSION = 'can use checklists';
    case CHECKLIST_EDIT_PERMISSION = 'can edit checklist';

    case AVAILABILITY_MANAGEMENT = 'can manage availability';

    case CREATE_EVENTS_WHEN_CREATING_PROJECT = 'can create events when creating a project';

    case INVENTORY_STOCK_MANAGE = 'can manage inventory stock';

    case INVENTORY_PLANER = 'can plan inventory';

    case INVENTORY_SETTINGS = 'inventory.settings';

    case CAN_VIEW_PRIVATE_USER_INFO = 'can view private user info';

    case CAN_SEE_PLANNING_CALENDAR = 'can see planning calendar';

    case CAN_EDIT_PLANNING_CALENDAR = 'can edit planning calendar';

    case CAN_PLAN_FIXED_IN_PLANNING_CALENDAR = 'can plan fixed in planning calendar';

    case SET_CREATE_EDIT = 'set.create_edit';
    case SET_DELETE = 'set.delete';
    case INVENTORY_CREATE_EDIT = 'inventory.create_edit';
    case INVENTORY_DELETE = 'inventory.delete';
    case INVENTORY_DISPOSITION = 'inventory.disposition';
    case MATERIAL_ISSUE_LOG_VIEW = 'can view material issue log';
    case SHIFT_SETTINGS_VIEW_EDIT = 'shift.settings_view_edit';
    case SHIFT_SETTINGS_GENERAL_VIEW = 'shift.settings.general.view';
    case SHIFT_SETTINGS_GENERAL_EDIT = 'shift.settings.general.edit';
    case SHIFT_SETTINGS_DAY_SERVICES_VIEW = 'shift.settings.day_services.view';
    case SHIFT_SETTINGS_DAY_SERVICES_EDIT = 'shift.settings.day_services.edit';
    case SHIFT_SETTINGS_WORK_TIME_PATTERNS_VIEW = 'shift.settings.work_time_patterns.view';
    case SHIFT_SETTINGS_WORK_TIME_PATTERNS_EDIT = 'shift.settings.work_time_patterns.edit';
    case SHIFT_SETTINGS_USER_CONTRACTS_VIEW = 'shift.settings.user_contracts.view';
    case SHIFT_SETTINGS_USER_CONTRACTS_EDIT = 'shift.settings.user_contracts.edit';
    case SHIFT_SETTINGS_SHIFT_GROUPS_VIEW = 'shift.settings.shift_groups.view';
    case SHIFT_SETTINGS_SHIFT_GROUPS_EDIT = 'shift.settings.shift_groups.edit';
    case SHIFT_SETTINGS_SHIFT_TEMPLATES_VIEW = 'shift.settings.shift_templates.view';
    case SHIFT_SETTINGS_SHIFT_TEMPLATES_EDIT = 'shift.settings.shift_templates.edit';
    case SHIFT_SETTINGS_RULES_VIEW = 'shift.settings.rules.view';
    case SHIFT_SETTINGS_RULES_EDIT = 'shift.settings.rules.edit';

    case DOCUMENT_REQUEST_CREATE = 'can create document requests';
    case DOCUMENT_REQUEST_EDIT = 'can edit document requests';

    case CRM_VIEW = 'can view crm';
    case CRM_MANAGER = 'crm manager';

    case BI_EXPORT = 'can export bi data';

    case BI_DASHBOARD = 'can view bi dashboard';

    case INVITE_EXTERNAL = 'can invite externals';

    case CAN_VIEW_OWN_ROSTER = 'can view own roster';
    case CAN_VIEW_OWN_UNCOMMITTED_SHIFTS = 'can view own uncommitted shifts';
    case CAN_SUBSCRIBE_SHIFT_CALENDAR = 'can subscribe shift calendar';

    case CAN_VIEW_SHIFT_USER_KPIS = 'can view shift user kpis';

    case CAN_VIEW_SHIFT_WORKER_HOURS = 'can view shift worker hours';

    case CAN_PAY_OUT_OVERTIME = 'can pay out overtime';

    case DAY_REMARKS_VIEW = 'can view day remarks';
    case DAY_REMARKS_EDIT = 'can edit day remarks';

    case WEBHOOKS_MANAGE = 'manage webhooks';
}
