export function isProjectMember(projectMembers, userId){

    return true
}
export function isAdmin(){
    return is('artwork admin');
}

// Projects
const PROJECT_MANAGEMENT = 'management projects';
const PROJECT_DELETE = 'delete projects';
const PROJECT_VIEW = 'view projects';
const ADD_EDIT_OWN_PROJECT = 'create and edit own project';
const WRITE_PROJECTS = 'write projects';
const PROJECT_BUDGET_ADMIN = 'access project budgets';

const PROJECT_BUDGET_VERIFIED_ADD_REMOVE = 'can add and remove verified states';
const PROJECT_BUDGET_SEE_DOCS_CONTRACTS = 'can see, edit and delete project contracts and docs';

// ROOM
const EVENT_REQUEST = 'request room occupancy';
const ROOM_REQUEST_READING_DETAILS = 'read details room request';
const ROOM_REQUEST_CONFIRM = 'confirm prioritize edit room requests';
const ROOM_UPDATE = 'create, delete and update rooms';

// Docs & Budget
const CONTRACT_EDIT_UPLOAD = 'view edit upload contracts';
const MONEY_SOURCE_EDIT_VIEW_ADD = 'view edit add money_sources';
const CONTRACT_SEE_DOWNLOAD = 'can see and download contract modules';
const MONEY_SOURCE_EDIT_DELETE = 'can edit and delete money sources';

// System
const USER_UPDATE = 'usermanagement';
const CHECKLIST_SETTINGS_ADMIN = 'admin checklistTemplates';
const TEAM_UPDATE = 'teammanagement';
const DEPARTMENT_UPDATE = 'update departments';
const ROOM_ADMIN = 'admin rooms';
const SETTINGS_UPDATE = 'change tool settings';
const PROJECT_SETTINGS_UPDATE = 'change project settings';
const EVENT_SETTINGS_UPDATE = 'change event settings';

const SYSTEM_NOTIFICATION = 'change system notification';

// deleted
//const PROJECT_DELETE = 'delete projects';
const EVENT_TYPE_SETTINGS_ADMIN = 'admin eventTypeSettings';
const PROJECT_ADMIN = 'admin projects';
const CHECKLIST_UPDATE = 'update checklists';

const CHECKLIST_VIEW = 'view checklists';
const PROJECT_SETTINGS_ADMIN = 'admin projectSettings';
const CHECKLIST_DELETE = 'delete checklists';
const GLOBAL_NOTIFICATION_ADMIN = 'admin globalNotification';
const PROJECT_UPDATE = 'create and edit projects';

/*******************************************************************/
/*********************** Project Permissions ***********************/
/*******************************************************************/

/**
 * Function to check if member can be project manager
 * @returns {*}
 */
export function canManagementProjects(){
    return can(PROJECT_MANAGEMENT);
}

/**
 * function to check if member is allowed to delete projects
 * @returns {*}
 */
export function canDeleteProjects(){
    return can(PROJECT_DELETE);
}

/**
 * function to check if member is allowed to see projects
 * @returns {*}
 */
export function viewProjects(){
    return can(PROJECT_VIEW);
}

/**
 * function to check if member is allowed to create own projects
 * @returns boolean
 */
export function createOwnProjects(){
    return can(ADD_EDIT_OWN_PROJECT)
}

/**
 * function to check if member is allowed to write all projects
 * @returns {*}
 */
export function writeAccessOnAllProjects(){
    return can(WRITE_PROJECTS);
}
/**
 * function to check if member is allowed to write all budgets on projects
 * @returns {*}
 */
export function canSeeEditAllBudgets(){
    return can(PROJECT_BUDGET_ADMIN)
}






