<?php

use App\Http\Controllers\AppController;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\BudgetTemplateController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CellCalculationsController;
use App\Http\Controllers\CellCommentsController;
use App\Http\Controllers\ChecklistController;
use App\Http\Controllers\ChecklistTemplateController;
use App\Http\Controllers\CollectingSocietyController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\CompanyTypeController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\ContractModuleController;
use App\Http\Controllers\ContractTypeController;
use App\Http\Controllers\CopyrightController;
use App\Http\Controllers\CostCenterController;
use App\Http\Controllers\CraftController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventTypeController;
use App\Http\Controllers\ExportPDFController;
use App\Http\Controllers\FilterController;
use App\Http\Controllers\FreelancerController;
use App\Http\Controllers\FreelancerVacationController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\GlobalNotificationController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\MoneySourceCategoryController;
use App\Http\Controllers\MoneySourceController;
use App\Http\Controllers\MoneySourceFileController;
use App\Http\Controllers\MoneySourceReminderController;
use App\Http\Controllers\MoneySourceTaskController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PresetShiftController;
use App\Http\Controllers\PresetTimeLineController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectFileController;
use App\Http\Controllers\ProjectStatesController;
use App\Http\Controllers\ProjectHeadlineController;
use App\Http\Controllers\RoomAttributeController;
use App\Http\Controllers\RoomCategoryController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\RoomFileController;
use App\Http\Controllers\RowCommentController;
use App\Http\Controllers\SectorController;
use App\Http\Controllers\ServiceProviderContactsController;
use App\Http\Controllers\ServiceProviderController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\ShiftFilterController;
use App\Http\Controllers\ShiftPresetController;
use App\Http\Controllers\ShiftSettingsController;
use App\Http\Controllers\SubEventsController;
use App\Http\Controllers\SumCommentController;
use App\Http\Controllers\SumDetailsController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskTemplateController;
use App\Http\Controllers\UserCalendarFilterController;
use App\Http\Controllers\UserCommentedBudgetItemsSettingController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserShiftCalendarFilterController;
use App\Http\Controllers\UserVacationsController;
use App\Http\Middleware\CanEditProject;
use App\Http\Middleware\CanViewRoom;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', [AppController::class, 'index']);

Route::get('/password_feedback', [AppController::class, 'getPasswordScore']);

Route::get('/setup', [AppController::class, 'showSetupPage'])->name('setup');
Route::post('/setup', [AppController::class, 'createAdmin'])->name('setup.create');

Route::get('/users/invitations/accept', [InvitationController::class, 'accept']);
Route::post('/users/invitations/accept', [InvitationController::class, 'createUser'])->name('invitation.accept');

Route::get('/reset-password', [UserController::class, 'resetPassword'])->name('reset_user_password');


Route::group(['middleware' => ['auth:sanctum', 'verified']], function (): void {
    // TOOL SETTING ROUTE
    Route::group(['prefix' => 'tool'], function (): void {
        Route::get('/settings', function () {
            return Inertia::render('Settings/ToolSettings');
        })->name('tool.settings');
        Route::put('/settings', [AppController::class, 'updateTool'])->name('tool.update');
        Route::put('/settings/email', [AppController::class, 'updateEmailSettings'])->name('tool.updateMail');
    });

    Route::group(['middleware' => ['can:can edit and delete money sources']], function (): void {
        Route::get('/projects/{project}/budget', [ProjectController::class, 'projectBudgetTab'])
            ->name('projects.show.budget');

        Route::delete('/money_sources/{moneySource}', [MoneySourceController::class, 'destroy']);
    });

    Route::group(['middleware' => ['can:view edit add money_sources']], function (): void {
        Route::get('/money_sources', [MoneySourceController::class, 'index'])->name('money_sources.index');
        Route::get('/money_sources/{moneySource}', [MoneySourceController::class, 'show'])->name('money_sources.show');
    });

    //Hints
    Route::post('/toggle/hints', [AppController::class, 'toggle_hints'])->name('toggle.hints');
    Route::post(
        '/toggle/calendar_settings_project_status',
        [AppController::class, 'toggle_calendar_settings_project_status']
    )->name('toggle.calendar_settings_project_status');
    Route::post('/toggle/calendar_settings_options', [AppController::class, 'toggle_calendar_settings_options'])
        ->name('toggle.calendar_settings_options');
    Route::post(
        '/toggle/calendar_settings_project_management',
        [AppController::class, 'toggle_calendar_settings_project_management']
    )->name('toggle.calendar_settings_project_management');
    Route::post(
        '/toggle/calendar_settings_repeating_events',
        [AppController::class, 'toggle_calendar_settings_repeating_events']
    )->name('toggle.calendar_settings_repeating_events');
    Route::post(
        '/toggle/calendar_settings_work_shifts',
        [AppController::class, 'toggle_calendar_settings_work_shifts']
    )->name('toggle.calendar_settings_work_shifts');

    Route::get('/dashboard', [EventController::class, 'showDashboardPage'])->name('dashboard');
    Route::get('/checklist/templates', function () {
        return Inertia::render('ChecklistTemplates/Edit');
    })->name('checklistTemplates.edit');

    //Invitations
    Route::get('/users/invitations', [InvitationController::class, 'index'])->name('user.invitations');
    Route::get('/users/invitations/invite', [InvitationController::class, 'invite'])->name('user.invite');
    Route::get('/users/invitations/{invitation}/edit', [InvitationController::class, 'edit'])
        ->name('user.invitations.edit');
    Route::post('/users/invitations', [InvitationController::class, 'store'])->name('invitations.store');
    Route::patch('/users/invitations/{invitation}', [InvitationController::class, 'update']);
    Route::delete('/users/invitations/{invitation}', [InvitationController::class, 'destroy']);

    Route::patch('/users/{user}/calendar-settings', [UserController::class, 'updateCalendarSettings'])
        ->name('user.calendar_settings.update');

    //Users
    Route::get('/users', [UserController::class, 'index'])->name('users');
    Route::get('/users/search', [UserController::class, 'search'])->name('users.search');
    Route::get('/users/money_source_search', [UserController::class, 'moneySourceSearch'])
        ->name('users.money_source_search');
    Route::get('/users/{user}/info', [UserController::class, 'editUserInfo'])->name('user.edit.info');
    Route::get('/users/{user}/shiftplan', [UserController::class, 'editUserShiftplan'])->name('user.edit.shiftplan');
    Route::get('/users/{user}/terms', [UserController::class, 'editUserTerms'])->name('user.edit.terms');
    Route::get('/users/{user}/permissions', [UserController::class, 'editUserPermissions'])
        ->name('user.edit.permissions');
    Route::get('/users/{user}/workProfile', [UserController::class, 'editUserWorkProfile'])->can('can:can manage workers')
        ->name('user.edit.workProfile');
    Route::patch('/users/{user}/edit', [UserController::class, 'update'])->name('user.update');
    Route::patch('/users/{user}/checklists', [UserController::class, 'updateChecklistStatus'])
        ->name('user.checklists.update');
    Route::patch('/users/{user}/areas', [UserController::class, 'updateAreaStatus'])->name('user.areas.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('user.destroy');
    Route::patch('/users/{user}', [UserController::class, 'temporaryUserUpdate'])->name('update.user.temporary');
    Route::patch('/users/{user}/conditions', [UserController::class, 'updateUserTerms'])->name('user.update.terms');
    Route::post('/users/{user}/photo', [UserController::class, 'updateUserPhoto'])->name('user.update.photo');

    Route::post('/users/reset-password', [UserController::class, 'resetUserPassword'])->name('user.reset.password');
    Route::patch('/users/{user}/updateCraftSettings', [UserController::class, 'updateCraftSettings'])
        ->name('user.update.craftSettings');
    Route::patch('/users/{user}/workProfile', [UserController::class, 'updateWorkProfile'])
        ->name('user.update.workProfile');
    Route::patch('/users/{user}/assignCraft', [UserController::class, 'assignCraft'])->name('user.assign.craft');
    Route::delete('/users/{user}/removeCraft/{craft}', [UserController::class, 'removeCraft'])
        ->name('user.remove.craft');

    //Departments
    Route::get('/departments', [DepartmentController::class, 'index'])->name('departments');
    Route::get('/departments/search', [DepartmentController::class, 'search'])->name('departments.search');
    Route::post('/departments', [DepartmentController::class, 'store'])->name('departments.store');
    Route::get('/departments/{department}', [DepartmentController::class, 'show'])->name('departments.show');
    Route::get('/departments/{department}/edit', [DepartmentController::class, 'edit'])->name('departments.profile');
    Route::patch('/departments/{department}', [DepartmentController::class, 'update'])->name('departments.edit');
    Route::delete('/departments/{department}', [DepartmentController::class, 'destroy']);

    //Projects
    Route::get('/projects', [ProjectController::class, 'index'])->name('projects');
    Route::get('/projects/search', [ProjectController::class, 'search'])->name('projects.search');
    Route::get('/projects/search/single', [ProjectController::class, 'searchProjectsWithoutGroup'])
        ->name('projects.search.single');
    Route::get('/projects/trashed', [ProjectController::class, 'getTrashed'])->name('projects.trashed');
    Route::get('/projects/users_departments/search', [ProjectController::class, 'searchDepartmentsAndUsers'])
        ->name('users_departments.search');
    Route::get('/projects/create', [ProjectController::class, 'create'])->name('projects.create');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::get(
        '/projects/export/budget/{startBudgetDeadline}/{endBudgetDeadline}',
        [ProjectController::class, 'projectsBudgetByBudgetDeadlineExport']
    )->name('projects.export.budgetByBudgetDeadline');
    Route::post('/projects/{project}/updateKeyVisual', [ProjectController::class, 'updateKeyVisual'])
        ->name('projects_key_visual.update');
    Route::post('/projects/{project}/duplicate', [ProjectController::class, 'duplicate'])->name('projects.duplicate');
    Route::get('/projects/{project}/edit', [ProjectController::class, 'edit'])
        ->middleware(['can:edit projects', CanEditProject::class]);

    Route::patch('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
    Route::patch('/projects/{project}/shiftDescription', [ProjectController::class, 'updateShiftDescription'])
        ->name('projects.update.shift_description');
    Route::patch('/projects/{project}/shiftContacts', [ProjectController::class, 'updateShiftContacts'])
        ->name('projects.update.shift_contacts');
    Route::patch(
        '/projects/{project}/shiftRelevantEventTypes',
        [ProjectController::class, 'updateShiftRelevantEventTypes']
    )->name('projects.update.shift_event_types');
    Route::patch('/projects/{project}/attributes', [ProjectController::class, 'updateAttributes'])
        ->name('projects.update_attributes');
    Route::patch('/projects/{project}/updateDescription', [ProjectController::class, 'updateDescription'])
        ->name('projects.update_description');
    Route::delete('/projects/{id}/force', [ProjectController::class, 'forceDelete'])->name('projects.force');
    Route::patch('/projects/{id}/restore', [ProjectController::class, 'restore'])->name('projects.restore');
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy']);

    Route::middleware([CanEditProject::class])->group(function (): void {
        Route::patch('/projects/{project}/team', [ProjectController::class, 'updateTeam'])
            ->name('projects.update_team');
    });

    //ProjectTabs
    Route::get('/projects/{project}/info', [ProjectController::class, 'projectInfoTab'])->name('projects.show.info');
    Route::get('/projects/{project}/calendar', [ProjectController::class, 'projectCalendarTab'])
        ->name('projects.show.calendar');
    Route::get('/projects/{project}/checklist', [ProjectController::class, 'projectChecklistTab'])
        ->name('projects.show.checklist');
    Route::get('/projects/{project}/shift', [ProjectController::class, 'projectShiftTab'])->name('projects.show.shift')->can('can:can plan shifts');
    Route::get('/projects/{project}/export/budget', [ProjectController::class, 'projectBudgetExport'])
        ->name('projects.export.budget');
    Route::get('/projects/{project}/comment', [ProjectController::class, 'projectCommentTab'])
        ->name('projects.show.comment');

    //Project Entrance & registration
    Route::patch('/projects/{project}/entrance', [ProjectController::class, 'updateEntranceData'])
        ->name('projects.entrance.update');

    //ProjectFiles
    Route::post('/projects/{project}/files', [ProjectFileController::class, 'store'])->name('project_files.store');
    Route::post('/project_files/{project_file}', [ProjectFileController::class, 'update'])
        ->name('project_files.update');
    Route::get('/project_files/{project_file}', [ProjectFileController::class, 'download'])->name('download_file');
    Route::delete('/project_files/{project_file}', [ProjectFileController::class, 'destroy']);
    Route::delete('/project_files/{id}/force_delete', [ProjectFileController::class, 'forceDelete']);

    //MoneySourceFiles
    Route::post('/money_sources/{money_source}/files', [MoneySourceFileController::class, 'store'])
        ->name('money_sources_files.store');
    Route::post('/money_sources/{money_source_file}/files/update', [MoneySourceFileController::class, 'update'])
        ->name('money_sources_files.update');
    Route::get('/money_sources/{money_source_file}/files', [MoneySourceFileController::class, 'download'])
        ->name('money_sources_download_file');
    Route::delete(
        '/money_sources/{money_source}/{money_source_file}/files',
        [MoneySourceFileController::class, 'destroy']
    )->name('money_sources_delete_file');

    //Checklists
    Route::get('/checklists/create', [ChecklistController::class, 'create'])->name('checklists.create');
    Route::post('/checklists', [ChecklistController::class, 'store'])->name('checklists.store');
    Route::get('/checklists/{checklist}', [ChecklistController::class, 'show']);
    Route::get('/checklists/{checklist}/edit', [ChecklistController::class, 'edit']);
    Route::patch('/checklists/{checklist}', [ChecklistController::class, 'update'])->name('checklists.update');
    Route::delete('/checklists/{checklist}', [ChecklistController::class, 'destroy']);

    //ChecklistTemplates
    Route::get('/checklist_templates', [ChecklistTemplateController::class, 'index'])
        ->name('checklist_templates.management');
    Route::get('/checklist_templates/create', [ChecklistTemplateController::class, 'create'])
        ->name('checklist_templates.create');
    Route::get('/checklist_templates/search', [ChecklistTemplateController::class, 'search'])
        ->name('checklist_templates.search');
    Route::post('/checklist_templates', [ChecklistTemplateController::class, 'store'])
        ->name('checklist_templates.store');
    Route::get('/checklist_templates/{checklist_template}', [ChecklistTemplateController::class, 'show']);
    Route::get('/checklist_templates/{checklist_template}/edit', [ChecklistTemplateController::class, 'edit'])
        ->name('checklist_templates.edit');
    Route::patch('/checklist_templates/{checklist_template}', [ChecklistTemplateController::class, 'update'])
        ->name('checklist_templates.update');
    Route::delete('/checklist_templates/{checklist_template}', [ChecklistTemplateController::class, 'destroy']);

    //TaskTemplates
    Route::get('/task_templates/create', [TaskTemplateController::class, 'create'])->name('task_templates.create');
    Route::post('/task_templates', [TaskTemplateController::class, 'store'])->name('task_templates.store');
    Route::get('/task_templates/{task_template}/edit', [TaskTemplateController::class, 'edit']);
    Route::patch('/task_templates/{task_template}', [TaskTemplateController::class, 'update']);
    Route::delete('/task_templates/{task_template}', [TaskTemplateController::class, 'destroy']);

    //Tasks
    Route::get('/tasks/create', [TaskController::class, 'create'])->name('tasks.create');
    Route::get('/tasks/own', [TaskController::class, 'indexOwnTasks'])->name('tasks.own');
    Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::get('/tasks/{task}/edit', [TaskController::class, 'edit']);
    Route::patch('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::patch('/money_source/tasks/{moneySourceTask}', [MoneySourceTaskController::class, 'markAsDone'])
        ->name('money_source.tasks.update');
    Route::put('/tasks/order', [TaskController::class, 'updateOrder']);
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy']);

    //Categories
    Route::get('/settings/projects', [CategoryController::class, 'index'])->name('project.settings');
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::patch('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);
    Route::patch('/categories/{category}/restore', [CategoryController::class, 'restore']);
    Route::delete('/categories/{id}/force', [CategoryController::class, 'forceDelete'])->name('categories.force');

    //Genres
    Route::post('/genres', [GenreController::class, 'store'])->name('genres.store');
    Route::patch('/genres/{genre}', [GenreController::class, 'update']);
    Route::delete('/genres/{genre}', [GenreController::class, 'destroy']);
    Route::patch('/genres/{genre}/restore', [GenreController::class, 'restore']);
    Route::delete('/genres/{id}/force', [GenreController::class, 'forceDelete'])->name('genres.force');

    //Sectors
    Route::post('/sectors', [SectorController::class, 'store'])->name('sectors.store');
    Route::patch('/sectors/{sector}', [SectorController::class, 'update']);
    Route::delete('/sectors/{sector}', [SectorController::class, 'destroy']);
    Route::patch('/sectors/{sector}/restore', [SectorController::class, 'restore']);
    Route::delete('/sectors/{id}/force', [SectorController::class, 'forceDelete'])->name('sectors.force');

    //Comments
    Route::get('/comments/create', [CommentController::class, 'create'])->name('comments.create');
    Route::post('/comments', [CommentController::class, 'store'])->name('comments.store');
    Route::patch('/comments/{comment}', [CommentController::class, 'update']);
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy']);

    //SumComments
    Route::post('/sum/comments', [SumCommentController::class, 'store'])->name('sum.comments.store');
    Route::delete('/sum/comments/{comment}', [SumCommentController::class, 'destroy'])->name('sum.comments.delete');

    //Areas
    Route::get('/areas', [AreaController::class, 'index'])->name('areas.management');
    Route::get('/areas/trashed', [AreaController::class, 'getTrashed'])->name('areas.trashed');
    Route::post('/areas', [AreaController::class, 'store'])->name('areas.store');
    Route::post('/areas/{area}/duplicate', [AreaController::class, 'duplicate'])->name('areas.duplicate');
    Route::patch('/areas/{area}', [AreaController::class, 'update'])->name('areas.update');
    Route::delete('/areas/{area}', [AreaController::class, 'destroy']);

    //Trash
    Route::delete('/areas/{id}/force', [AreaController::class, 'forceDelete'])->name('areas.force');
    Route::patch('/areas/{id}/restore', [AreaController::class, 'restore'])->name('areas.restore');

    //Rooms
    Route::post('/rooms', [RoomController::class, 'store'])->name('rooms.store');
    Route::get('/rooms/trashed', [RoomController::class, 'getTrashed'])->name('rooms.trashed');
    Route::get('/rooms/free', [RoomController::class, 'getAllDayFree'])->name('rooms.free');
    Route::post('/rooms/{room}/duplicate', [RoomController::class, 'duplicate'])->name('rooms.duplicate');
    Route::get('/rooms/{room}', [RoomController::class, 'show'])
        ->name('rooms.show')->middleware(['can:edit room', CanViewRoom::class]);
    ;
    Route::patch('/rooms/{room}', [RoomController::class, 'update'])->name('rooms.update');
    Route::put('/rooms/order', [RoomController::class, 'updateOrder']);
    Route::delete('/rooms/{room}', [RoomController::class, 'destroy']);

    //Trash
    Route::delete('/rooms/{id}/force', [RoomController::class, 'forceDelete'])->name('rooms.force');
    Route::patch('/rooms/{id}/restore', [RoomController::class, 'restore'])->name('rooms.restore');

    //RoomFiles
    Route::post('/rooms/{room}/files', [RoomFileController::class, 'store'])->name('room_files.store');
    Route::get('/room_files/{room_file}', [RoomFileController::class, 'download'])->name('download_room_file');
    Route::delete('/room_files/{room_file}', [RoomFileController::class, 'destroy']);
    Route::delete('/room_files/{id}/force_delete', [RoomFileController::class, 'forceDelete']);

    //Room Categories
    Route::post('/rooms/categories', [RoomCategoryController::class, 'store'])
        ->name('room_categories.store');
    Route::delete('/rooms/categories/{roomCategory}', [RoomCategoryController::class, 'destroy'])
        ->name('room_categories.destroy');

    //Room Attributes
    Route::post('/rooms/attributes', [RoomAttributeController::class, 'store'])
        ->name('room_attribute.store');
    Route::delete('/rooms/attributes/{roomAttribute}', [RoomAttributeController::class, 'destroy'])
        ->name('room_attribute.destroy');

    //Filters
    Route::get('/filters', [FilterController::class, 'index']);
    Route::post('/filters', [FilterController::class, 'store']);
    Route::delete('/filters/{filter}', [FilterController::class, 'destroy']);

    //Shift Filters
    Route::get('/shifts/filters', [ShiftFilterController::class, 'index']);
    Route::post('/shifts/filters', [ShiftFilterController::class, 'store']);
    Route::delete('/shifts/filters/{filter}', [ShiftFilterController::class, 'destroy']);

    //Event Views
    Route::get('/calendar/view', [EventController::class, 'viewEventIndex'])->name('events');
    Route::get('/events/requests', [EventController::class, 'viewRequestIndex'])->name('events.requests');
    Route::get('/events/trashed', [EventController::class, 'getTrashed'])->name('events.trashed');

    // Event Api
    Route::get('/events', [EventController::class, 'eventIndex'])->name('events.index');
    Route::get('/events/collision', [EventController::class, 'getCollisionCount'])->name('events.collisions');
    Route::post('/events', [EventController::class, 'storeEvent'])->name('events.store');
    Route::put('/events/{event}', [EventController::class, 'updateEvent'])->name('events.update');
    Route::delete('/events/{event}', [EventController::class, 'destroy'])->name('events.delete');
    Route::post('/events/{event}/by/notification', [EventController::class, 'destroyByNotification'])
        ->name('events.delete.by.notification');
    Route::delete('/events/{event}/shifts', [EventController::class, 'destroyShifts'])->name('events.shifts.delete');

    Route::put('/event/requests/{event}/accept', [EventController::class, 'acceptEvent'])->name('events.accept');
    Route::put('/event/requests/{event}/decline', [EventController::class, 'declineEvent'])->name('events.decline');
    Route::post('/event/answer/{event}', [EventController::class, 'answerOnEvent'])->name('event.answer');

    //Trash
    Route::delete('/events/{id}/force', [EventController::class, 'forceDelete'])->name('events.force');
    Route::patch('/events/{id}/restore', [EventController::class, 'restore'])->name('events.restore');

    //Shifts
    Route::get('/shifts/view', [EventController::class, 'viewShiftPlan'])->name('shifts.plan')->can('can:can view shift plan');
    Route::get('/shifts/presets', [ShiftPresetController::class, 'index'])->name('shifts.presets');
    Route::post('/shift/{shiftPreset}/preset/store', [ShiftPresetController::class, 'addNewShift'])
        ->name('shift.preset.store');
    Route::post('/shifts/commit', [EventController::class, 'commitShifts'])->name('shifts.commit');

    //EventTypes
    Route::get('/event_types', [EventTypeController::class, 'index'])->name('event_types.management')->can('can:change event settings');
    Route::post('/event_types', [EventTypeController::class, 'store'])->name('event_types.store');
    Route::get('/event_types/{event_type}', [EventTypeController::class, 'show'])->name('event_types.show');
    Route::patch('/event_types/{event_type}', [EventTypeController::class, 'update'])->name('event_types.update');
    Route::delete('/event_types/{event_type}', [EventTypeController::class, 'destroy']);

    // notification
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/collision/room', [RoomController::class, 'collisionsCount'])->name('collisions.room');
    Route::patch('/notifications', [NotificationController::class, 'setOnRead'])->name('notifications.setReadAt');
    Route::patch('/user/settings/group', [NotificationController::class, 'toggleGroup'])->name('notifications.group');
    Route::patch('/user/settings/{setting}', [NotificationController::class, 'updateSetting'])
        ->name('notifications.settings');
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy'])->name('notifications.delete');
    Route::post('/notifications/{notificationKey}', [EventController::class, 'deleteOldNotifications'])
        ->name('event.notification.delete');

    //globalNotification
    Route::get('/globalNotification', [GlobalNotificationController::class, 'show'])->name('global_notification.show');
    Route::post('/globalNotification/create', [GlobalNotificationController::class, 'store'])
        ->name('global_notification.store');
    Route::delete('/globalNotification/{globalNotification}', [GlobalNotificationController::class, 'destroy'])
        ->name('global_notification.destroy');

    // Money Sources
    Route::get('/money_sources/settings', [MoneySourceController::class, 'showSettings'])
        ->name('money_sources.settings');
    Route::get('/money_sources/search', [MoneySourceController::class, 'search'])->name('money_sources.search');
    Route::patch('/money_sources/{moneySource}', [MoneySourceController::class, 'update'])
        ->name('money_sources.update');
    Route::patch('/money_sources/{moneySource}/users', [MoneySourceController::class, 'updateUsers'])
        ->name('money_sources.update_users');
    Route::patch('/money_sources/{moneySource}/projects', [MoneySourceController::class, 'updateProjects'])
        ->name('money_sources.update_projects');
    Route::post('/money_sources', [MoneySourceController::class, 'store'])->name('money_sources.store');
    Route::post('/money_sources/{moneySource}/duplicate', [MoneySourceController::class, 'duplicate'])
        ->name('money_sources.duplicate');
    Route::post('/money_sources/{moneySource}/pin', [MoneySourceController::class, 'pin'])->name('money_sources.pin');
    Route::delete('/money_sources/{moneySource}', [MoneySourceController::class, 'destroy']);
    Route::post('/money_sources/{moneySource}/categories', [MoneySourceController::class, 'syncCategories'])
        ->name('money_sources.categories.sync');

    // MoneySourceCategories
    Route::post('/money_source/categories', [MoneySourceCategoryController::class, 'store'])
        ->name('money_source_categories.store');
    Route::delete('/money_source/categories/{moneySourceCategory}', [MoneySourceCategoryController::class, 'destroy'])
        ->name('money_source_categories.destroy');

    // MoneySourceReminder
    Route::resource('money_source.reminder', MoneySourceReminderController::class)->only('store');

    //Contracts
    Route::get('/contracts/view', [ContractController::class, 'viewIndex'])->name('contracts.view.index');
    Route::get('/contracts', [ContractController::class, 'index'])->name('contracts.index');
    Route::post('/projects/{project}/contracts', [ContractController::class, 'store'])->name('contracts.store');
    Route::get('/contracts/{contract}', [ContractController::class, 'show'])->name('contracts.show');
    Route::get('/contracts/{contract}/download', [ContractController::class, 'download'])->name('contracts.download');
    Route::patch('/contracts/{contract}', [ContractController::class, 'update'])->name('contracts.update');
    Route::delete('/contracts/{contract}', [ContractController::class, 'destroy']);
    Route::post('/contract', [ContractController::class, 'storeFile'])->name('contracts.store.file');

    //ContractModules
    Route::get('/contract_modules', [ContractModuleController::class, 'index'])->name('contracts.module.management');
    Route::post('/contract_modules', [ContractModuleController::class, 'store'])->name('contracts.module.store');
    Route::get('/contract_modules/{module}/download', [ContractModuleController::class, 'download'])
        ->name('contracts.module.download');
    Route::delete('/contract_modules/{module}', [ContractModuleController::class, 'destroy']);

    //MoneySourceTasks
    Route::patch('money_source/task/{moneySourceTask}/done', [MoneySourceTaskController::class, 'markAsDone'])
        ->name('money_source.task.done');
    Route::patch('money_source/task/{moneySourceTask}/undone', [MoneySourceTaskController::class, 'markAsUnDone'])
        ->name('money_source.task.undone');
    Route::post('/money_source/task', [MoneySourceTaskController::class, 'store'])->name('money_source.task.add');
    Route::post('/{event}/shift/preset/store', [ShiftPresetController::class, 'store'])->name('shift-presets.store');
    Route::delete('/user/{user}/calendar/filter/reset', [UserCalendarFilterController::class, 'reset'])
        ->name('reset.user.calendar.filter');
    Route::delete('/user/{user}/calendar/shift/filter/reset', [UserShiftCalendarFilterController::class, 'reset'])
        ->name('reset.user.shift.calendar.filter');
    Route::patch('/user/{user}/calendar/filter/update', [UserCalendarFilterController::class, 'update'])
        ->name('update.user.calendar.filter');
    Route::patch('/user/{user}/shift/calendar/filter/update', [UserShiftCalendarFilterController::class, 'update'])
        ->name('update.user.shift.calendar.filter');
    Route::patch('/user/{user}/calendar/filter/date/update', [UserCalendarFilterController::class, 'updateDates'])
        ->name('update.user.calendar.filter.dates');
    Route::patch(
        '/user/{user}/calendar/filter/single/update/calendar',
        [UserCalendarFilterController::class, 'singleValueUpdate']
    )->name('user.calendar.filter.single.value.update');
    Route::patch(
        '/user/{user}/calendar/filter/single/update/shift',
        [UserShiftCalendarFilterController::class, 'singleValueUpdate']
    )->name('user.shift.calendar.filter.single.value.update');
    Route::patch(
        '/user/{user}/shift/calendar/filter/date/update',
        [UserShiftCalendarFilterController::class, 'updateDates']
    )->name('update.user.shift.calendar.filter.dates');

    Route::resource(
        'user.commentedBudgetItemsSettings',
        UserCommentedBudgetItemsSettingController::class
    )->only(['store', 'update']);

    // Project Routes
    Route::group(['prefix' => 'project'], function (): void {
        // GET
        Route::get('/user/search', [ProjectController::class, 'projectUserSearch'])->name('project.user.search');
        Route::get('/{project}/download/keyVisual', [ProjectController::class, 'downloadKeyVisual'])
            ->name('project.download.keyVisual');

        // POST
        Route::post('/{shift}/add/user/{user}', [ShiftController::class, 'addShiftUser'])->name('add.shift.user');
        Route::post('/{shift}/add/{user}/master', [ShiftController::class, 'addShiftMaster'])->name('add.shift.master');
        Route::post('/{shift}/add/freelancer/{freelancer}', [ShiftController::class, 'addShiftFreelancer'])
            ->name('add.shift.freelancer');
        Route::post(
            '/{shift}/add/freelancer/{freelancer}/master',
            [ShiftController::class, 'addShiftFreelancerMaster']
        )->name('add.shift.freelancer.master');
        Route::post(
            '/{shift}/add/provider/{serviceProvider}',
            [ShiftController::class, 'addShiftProvider']
        )->name('add.shift.provider');
        Route::post(
            '/{shift}/add/provider/{serviceProvider}/master',
            [ShiftController::class, 'addShiftProviderMaster']
        )->name('add.shift.provider.master');
        Route::post('/timeline/add/{event}', [ProjectController::class, 'addTimeLineRow'])->name('add.timeline.row');
        Route::post('/{event}/shift/store', [ShiftController::class, 'store'])->name('event.shift.store');
        Route::post('/sums/money-source', [SumDetailsController::class, 'store'])
            ->name('project.sum.money.source.store');

        // PATCH
        Route::patch('/timelines/update', [ProjectController::class, 'updateTimeLines'])->name('update.timelines');
        Route::patch('/shifts/commit', [ShiftController::class, 'updateCommitments'])->name('update.shift.commitment');
        Route::patch('/{shift}/update', [ShiftController::class, 'updateShift'])->name('event.shift.update');
        Route::patch('/sums/money-source/{sumMoneySource}', [SumDetailsController::class, 'update'])
            ->name('project.sum.money.source.update');

        // DELETE
        Route::delete('/{shift}/remove/user/{user}', [ShiftController::class, 'removeUser'])->name('shifts.removeUser');
        Route::delete('/{shift}/remove/freelancer/{freelancer}', [ShiftController::class, 'removeFreelancer'])
            ->name('shifts.removeFreelancer');
        Route::delete('/{shift}/remove/provider/{serviceProvider}', [ShiftController::class, 'removeProvider'])
            ->name('shifts.removeProvider');
        Route::delete('/{shift}/remove/employees/master', [ShiftController::class, 'clearEmployeesAndMaster'])
            ->name('shifts.clearEmployeesAndMaster');
        Route::delete('/{shift}/destroy', [ShiftController::class, 'destroy'])->name('shifts.destroy');
        Route::delete('/timeline/delete/{timeLine}', [ProjectController::class, 'deleteTimeLineRow'])
            ->name('delete.timeline.row');
        Route::delete('/sums/money-source/{sumMoneySource}', [SumDetailsController::class, 'destroy'])
            ->name('project.sum.money.source.destroy');
        Route::delete('/group', [ProjectController::class, 'deleteProjectFromGroup'])->name('projects.group.delete');
        Route::delete('/{project}/delete/keyVisual', [ProjectController::class, 'deleteKeyVisual'])
            ->name('project.delete.keyVisual');

        Route::group(['prefix' => 'budget'], function (): void {
            // GET
            Route::get('/cell/comments', [CellCommentsController::class, 'get'])
                ->name('project.budget.cell.comment.get');

            // POST
            Route::post('/column/add', [ProjectController::class, 'addColumn'])->name('project.budget.column.add');
            Route::post('/cell-calculation/{cell}/add', [ProjectController::class, 'addCalculation'])
                ->name('project.budget.cell-calculation.add');
            Route::post('/sub-position/add', [ProjectController::class, 'addSubPosition'])
                ->name('project.budget.sub-position.add');
            Route::post('/main-position/add', [ProjectController::class, 'addMainPosition'])
                ->name('project.budget.main-position.add');
            Route::post('/sub-position-row/add', [ProjectController::class, 'addSubPositionRow'])
                ->name('project.budget.sub-position-row.add');
            Route::post('/cell/{columnCell}/comment/add', [CellCommentsController::class, 'store'])
                ->name('project.budget.cell.comment.store');
            Route::post('/row/{row}/comment/add', [RowCommentController::class, 'store'])
                ->name('project.budget.row.comment.store');
            Route::post('/duplicate/{column}/column', [ProjectController::class, 'duplicateColumn'])
                ->name('project.budget.column.duplicate');
            Route::post('/duplicate/{subPosition}/subpostion', [ProjectController::class, 'duplicateSubPosition'])
                ->name('project.budget.sub-position.duplicate');
            Route::post(
                '/duplicate/{mainPosition}/mainPosition',
                [ProjectController::class, 'duplicateMainPosition']
            )->name('project.budget.main-position.duplicate');
            Route::post(
                '/verified/main-position/request',
                [ProjectController::class, 'verifiedRequestMainPosition']
            )->name('project.budget.verified.main-position.request');
            Route::post('/verified/sub-position/request', [ProjectController::class, 'verifiedRequestSubPosition'])
                ->name('project.budget.verified.sub-position.request');
            Route::post('/verified/take-back/position', [ProjectController::class, 'takeBackVerification'])
                ->name('project.budget.take-back.verification');
            Route::post('/verified/remove/position', [ProjectController::class, 'removeVerification'])
                ->name('project.budget.remove.verification');
            Route::post('/template/{table}/create', [BudgetTemplateController::class, 'store'])
                ->name('project.budget.template.create');
            Route::post('/template/{table}/use', [BudgetTemplateController::class, 'useTemplate'])
                ->name('project.budget.template.use');
            Route::post('/template/use/project', [BudgetTemplateController::class, 'useTemplateFromProject'])
                ->name('project.budget.template.project');
            Route::post('/subposition/row/{subPositionRow}/duplicate', [ProjectController::class, 'duplicateRow'])
                ->name('project.budget.sub-position.duplicate.row');

            // PATCH
            Route::patch('/cell', [ProjectController::class, 'updateCellValue'])->name('project.budget.cell.update');
            Route::patch('/column/update-name', [ProjectController::class, 'updateColumnName'])
                ->name('project.budget.column.update-name');
            Route::patch('/table/update-name', [ProjectController::class, 'updateTableName'])
                ->name('project.budget.table.update-name');
            Route::patch('/main-position/update-name', [ProjectController::class, 'updateMainPositionName'])
                ->name('project.budget.main-position.update-name');
            Route::patch('/sub-position/update-name', [ProjectController::class, 'updateSubPositionName'])
                ->name('project.budget.sub-position.update-name');
            Route::patch('/cell-source/update', [ProjectController::class, 'updateCellSource'])
                ->name('project.budget.cell-source.update');
            Route::patch('/cell-calculation/update', [ProjectController::class, 'updateCellCalculation'])
                ->name('project.budget.cell-calculation.update');
            Route::patch('/row/{row}/commentedStatus', [ProjectController::class, 'updateCommentedStatusOfRow'])
                ->name('project.budget.row.commented');
            Route::patch(
                '/cell/{columnCell}/commentedStatus',
                [ProjectController::class, 'updateCommentedStatusOfCell']
            )->name('project.budget.cell.commented');
            Route::patch('/column-color/change', [ProjectController::class, 'changeColumnColor'])
                ->name('project.budget.column-color.change');
            Route::patch('/verified/main-position', [ProjectController::class, 'verifiedMainPosition'])
                ->name('project.budget.verified.main-position');
            Route::patch('/verified/sub-position', [ProjectController::class, 'verifiedSubPosition'])
                ->name('project.budget.verified.sub-position');
            Route::patch('/lock/column', [ProjectController::class, 'lockColumn'])->name('project.budget.lock.column');
            Route::patch('/unlock/column', [ProjectController::class, 'unlockColumn'])
                ->name('project.budget.unlock.column');
            Route::patch('/fix/sub-position', [ProjectController::class, 'fixSubPosition'])
                ->name('project.budget.fix.sub-position');
            Route::patch('/unfix/sub-position', [ProjectController::class, 'unfixSubPosition'])
                ->name('project.budget.unfix.sub-position');
            Route::patch('/fix/main-position', [ProjectController::class, 'fixMainPosition'])
                ->name('project.budget.fix.main-position');
            Route::patch('/unfix/main-position', [ProjectController::class, 'unfixMainPosition'])
                ->name('project.budget.unfix.main-position');
            Route::patch('/column/{column}/commented', [ProjectController::class, 'updateCommentedStatusOfColumn'])
                ->name('project.budget.column.update.commented');

            // DELETE
            Route::delete('/sub-position-row/{row}', [ProjectController::class, 'deleteRow'])
                ->name('project.budget.sub-position-row.delete');
            Route::delete('/cell/comment/{cellComment}', [CellCommentsController::class, 'destroy'])
                ->name('project.budget.cell.comment.delete');
            Route::delete('/cell/calculation/{cellCalculation}', [CellCalculationsController::class, 'destroy'])
                ->name('project.budget.cell.calculation.delete');
            Route::delete('/row/comment/{rowComment}', [RowCommentController::class, 'destroy'])
                ->name('project.budget.row.comment.delete');
            Route::delete('/column/{column}/delete', [ProjectController::class, 'columnDelete'])
                ->name('project.budget.column.delete');
            Route::delete('/main-position/{mainPosition}', [ProjectController::class, 'deleteMainPosition'])
                ->name('project.budget.main-position.delete');
            Route::delete('/sub-position/{subPosition}', [ProjectController::class, 'deleteSubPosition'])
                ->name('project.budget.sub-position.delete');
            Route::delete('/table/{table}', [ProjectController::class, 'deleteTable'])
                ->name('project.budget.table.delete');
        });
    });

    Route::patch('/project/{project}/budget/reset', [ProjectController::class, 'resetTable'])
        ->name('project.budget.reset.table');

    // Templates
    Route::get('/templates/index', [BudgetTemplateController::class, 'index'])->name('templates.view.index')->can('can:view budget templates');

    //CopyRight
    Route::post('/copyright', [CopyrightController::class, 'store'])->name('copyright.store');
    Route::patch('/copyright/{copyright}', [CopyrightController::class, 'update'])->name('copyright.update');

    //Cost Center
    Route::post('/cost_center', [CostCenterController::class, 'store'])->name('costCenter.store');
    Route::patch('/cost_center/{costCenter}', [CostCenterController::class, 'update'])->name('costCenter.update');

    // ContractTypes
    Route::get('/contract_types', [ContractTypeController::class, 'index'])->name('contract_types.index');
    Route::post('/contract_types', [ContractTypeController::class, 'store'])->name('contract_types.store');
    Route::delete('/contract_types/{contract_type}', [ContractTypeController::class, 'destroy'])
        ->name('contract_types.delete');
    Route::patch('/contract_types/{contract_type}/restore', [ContractTypeController::class, 'restore'])
        ->name('contract_types.restore');
    Route::delete('/contract_types/{id}/force', [ContractTypeController::class, 'forceDelete'])
        ->name('contract_types.force');

    // CompanyTypes
    Route::get('/company_types', [CompanyTypeController::class, 'index'])->name('company_types.index');
    Route::post('/company_types', [CompanyTypeController::class, 'store'])->name('company_types.store');
    Route::delete('/company_types/{company_type}', [CompanyTypeController::class, 'destroy'])
        ->name('company_types.delete');
    Route::patch('/company_types/{company_type}/restore', [CompanyTypeController::class, 'restore'])
        ->name('company_types.restore');
    Route::delete('/company_types/{id}/force', [CompanyTypeController::class, 'forceDelete'])
        ->name('company_types.force');

    // Collecting Societies
    Route::get('/collecting_societies', [CollectingSocietyController::class, 'index'])
        ->name('collecting_societies.index');
    Route::post('/collecting_societies', [CollectingSocietyController::class, 'store'])
        ->name('collecting_societies.store');
    Route::delete('/collecting_societies/{collecting_society}', [CollectingSocietyController::class, 'destroy'])
        ->name('collecting_societies.delete');
    Route::patch(
        '/collecting_societies/{collecting_society}/restore',
        [CollectingSocietyController::class, 'restore']
    )->name('collecting_societies.restore');
    Route::delete('/collecting_societies/{id}/force', [CollectingSocietyController::class, 'forceDelete'])
        ->name('collecting_societies.force');

    // Currencies
    Route::get('/currencies', [CurrencyController::class, 'index'])->name('currencies.index');
    Route::post('/currencies', [CurrencyController::class, 'store'])->name('currencies.store');
    Route::delete('/currencies/{currency}', [CurrencyController::class, 'destroy'])->name('currencies.delete');
    Route::patch('/currencies/{currency}/restore', [CurrencyController::class, 'restore'])->name('currencies.restore');
    Route::delete('/currencies/{id}/force', [CurrencyController::class, 'forceDelete'])->name('currencies.force');

    // Project Headlines
    Route::post('/project_headlines', [ProjectHeadlineController::class, 'store'])->name('project_headlines.store');
    Route::put('/project_headlines/order', [ProjectHeadlineController::class, 'updateOrder'])
        ->name('project_headlines.order');
    Route::patch('/project_headlines/{project_headline}', [ProjectHeadlineController::class, 'update'])
        ->name('project_headlines.update');
    Route::patch(
        '/project_headlines/{project_headline}/{project}/text',
        [ProjectHeadlineController::class, 'updateText']
    )->name('project_headlines.update.text');
    Route::delete(
        '/project_headlines/{project_headline}',
        [ProjectHeadlineController::class, 'destroy']
    )->name('project_headlines.delete');

    // Project States
    Route::post('/state', [ProjectStatesController::class, 'store'])->name('state.store');
    Route::patch('/project/{project}/state', [ProjectController::class, 'updateProjectState'])
        ->name('update.project.state');
    Route::delete('/state/{projectStates}', [ProjectStatesController::class, 'destroy'])->name('state.delete');
    Route::patch('/states/{state}/restore', [ProjectStatesController::class, 'restore'])->name('state.restore');
    Route::delete('/states/{id}/force', [ProjectStatesController::class, 'forceDelete'])->name('state.force');

    // Project Settings
    Route::get('projects/settings/trashed', [ProjectController::class, 'getTrashedSettings'])
        ->name('projects.settings.trashed');

    // Sub Event
    Route::post('/sub-event/add', [SubEventsController::class, 'store'])->name('subEvent.add');
    Route::delete('/sub-event/{subEvents}', [SubEventsController::class, 'destroy'])->name('subEvent.delete');
    Route::patch('/sub-event/{subEvents}', [SubEventsController::class, 'update'])->name('subEvent.update');

    // MultiEdit
    Route::patch('/multi-edit', [EventController::class, 'updateMultiEdit'])->name('multi-edit.save');
    Route::post('/multi-edit', [EventController::class, 'deleteMultiEdit'])->name('multi-edit.delete');

    // Calendar
    Route::get('/calendars/filters', [CalendarController::class, 'getFilters'])->name('calendar.filters');

    // Freelancer
    Route::get('/freelancer/{freelancer}', [FreelancerController::class, 'show'])->name('freelancer.show');
    Route::patch('/freelancer/update/{freelancer}', [FreelancerController::class, 'update'])->name('freelancer.update');
    Route::post('/freelancer/profile-image/{freelancer}', [FreelancerController::class, 'updateProfileImage'])
        ->name('freelancer.change.profile-image');
    Route::post('freelancer/add', [FreelancerController::class, 'store'])->name('freelancer.add');
    Route::delete('freelancer/{freelancer}', [FreelancerController::class, 'destroy'])->name('freelancer.destroy');
    Route::patch('/freelancer/{freelancer}/workProfile', [FreelancerController::class, 'updateWorkProfile'])->can('can:can manage workers')
        ->name('freelancer.update.workProfile');
    Route::patch('/freelancer/{freelancer}/terms', [FreelancerController::class, 'updateTerms'])
        ->name('freelancer.update.terms');
    Route::patch(
        '/freelancer/{freelancer}/updateCraftSettings',
        [FreelancerController::class, 'updateCraftSettings']
    )->name('freelancer.update.craftSettings');
    Route::patch(
        '/freelancer/{freelancer}/assignCraft',
        [FreelancerController::class, 'assignCraft']
    )->name('freelancer.assign.craft');
    Route::delete(
        '/freelancer/{freelancer}/removeCraft/{craft}',
        [FreelancerController::class, 'removeCraft']
    )->name('freelancer.remove.craft');

    // Vacation

    Route::post('/freelancer/vacation/{freelancer}/add', [\App\Http\Controllers\VacationController::class, 'storeFreelancerVacation'])
        ->name('freelancer.vacation.add');
    Route::patch('/freelancer/vacation/{freelancerVacation}/update', [\App\Http\Controllers\VacationController::class, 'update'])
        ->name('freelancer.vacation.update');
    Route::post('/freelancer/{freelancer}/masters', [\App\Http\Controllers\FreelancerController::class, 'update_freelancer_can_master'])
        ->name('freelancer.update.can_master');
    Route::post('/freelancer/{freelancer}/workings', [\App\Http\Controllers\FreelancerController::class, 'update_work_data'])
        ->name('freelancer.update.work_data');
    Route::delete('/freelancer/vacation/{freelancerVacation}/delete', [\App\Http\Controllers\VacationController::class, 'destroy'])
        ->name('freelancer.vacation.delete');


    // vacation and availability
    Route::patch('/update/vacation/{vacation}', [\App\Http\Controllers\VacationController::class, 'update'])
        ->name('update.vacation');

    Route::patch('/update/availability/{availability}', [\App\Http\Controllers\AvailabilityController::class, 'update'])
        ->name('update.availability');

    Route::delete('/delete/availability/{availability}', [\App\Http\Controllers\AvailabilityController::class, 'destroy'])
        ->name('delete.availability');

    Route::delete('/delete/vacation/{vacation}', [\App\Http\Controllers\VacationController::class, 'destroy'])
        ->name('delete.vacation');

    // delete.availability.series
    Route::delete('/delete/availability/series/{availabilitySeries}', [\App\Http\Controllers\AvailabilityController::class, 'destroySeries'])
        ->name('delete.availability.series');

    // delete.vacation.series
    Route::delete('/delete/vacation/series/{vacationSeries}', [\App\Http\Controllers\VacationController::class, 'destroySeries'])
        ->name('delete.vacation.series');


    // Service Provider
    Route::get('/service-provider/{serviceProvider}', [ServiceProviderController::class, 'show'])
        ->name('service_provider.show');
    Route::patch('/service-provider/update/{serviceProvider}', [ServiceProviderController::class, 'update'])
        ->name('service_provider.update');
    Route::post(
        '/service-provider/profile-image/{serviceProvider}',
        [ServiceProviderController::class, 'updateProfileImage']
    )->name('service_provider.change.profile-image');
    Route::post('service-provider/add', [ServiceProviderController::class, 'store'])->name('service_provider.add');
    Route::delete('service-provider/{serviceProvider}', [ServiceProviderController::class, 'destroy'])
        ->name('service_provider.destroy');
    Route::patch(
        '/service-provider/{serviceProvider}/workProfile',
        [ServiceProviderController::class, 'updateWorkProfile']
    )->name('service_provider.update.workProfile')->can('can:can manage workers');
    Route::patch(
        '/service-provider/{serviceProvider}/terms',
        [ServiceProviderController::class, 'updateTerms']
    )->name('service_provider.update.terms');
    Route::patch(
        '/service-provider/{serviceProvider}/updateCraftSettings',
        [ServiceProviderController::class, 'updateCraftSettings']
    )->name('service_provider.update.craftSettings');
    Route::patch(
        '/service-provider/{serviceProvider}/assignCraft',
        [ServiceProviderController::class, 'assignCraft']
    )->name('service_provider.assign.craft');
    Route::delete(
        '/service-provider/{serviceProvider}/removeCraft/{craft}',
        [ServiceProviderController::class, 'removeCraft']
    )->name('service_provider.remove.craft');
    Route::delete(
        '/service-provider/contact/{serviceProviderContacts}/delete/',
        [ServiceProviderContactsController::class, 'destroy']
    )->name('service-provider.contact.delete');
    Route::post(
        '/service-provider/contact/{serviceProvider}/add/',
        [ServiceProviderContactsController::class, 'store']
    )->name('service-provider.contact.store');
    Route::patch(
        '/service-provider/contact/{serviceProviderContacts}/update/',
        [ServiceProviderContactsController::class, 'update']
    )->name('service-provider.contact.update');

    // Vacation

    Route::post('/user/vacation/{user}/add', [\App\Http\Controllers\VacationController::class, 'store'])->name('user.vacation.add');
    Route::patch('/user/vacation/{userVacations}/update', [\App\Http\Controllers\VacationController::class, 'update'])->name('user.vacation.update');
    Route::delete('/user/vacation/{userVacations}/delete', [\App\Http\Controllers\VacationController::class, 'destroy'])->name('user.vacation.delete');


    Route::group(['prefix' => 'settings'], function (): void {
        Route::get('shift', [ShiftSettingsController::class, 'index'])->name('shift.settings');
        Route::post('shift/add/craft', [CraftController::class, 'store'])->name('craft.store');
        Route::patch('shift/update/craft/{craft}', [CraftController::class, 'update'])->name('craft.update');
        Route::delete('shift/delete/craft/{craft}', [CraftController::class, 'destroy'])->name('craft.delete');
        Route::patch(
            'shift/update/relevant/event-type/{eventType}',
            [EventTypeController::class, 'updateRelevant']
        )->name('event-type.update.relevant');
    });

    Route::post('/empty/preset/store', [ShiftPresetController::class, 'storeEmpty'])->name('empty.presets.store');
    Route::delete('/preset/{presetShift}/shift/delete', [PresetShiftController::class, 'destroy'])
        ->name('preset.shift.destroy');
    Route::patch('/preset/{presetShift}/shift/update', [PresetShiftController::class, 'update'])
        ->name('shift.preset.update');
    Route::delete('/shift/preset/{shiftPreset}/destroy', [ShiftPresetController::class, 'destroy'])
        ->name('destroy.shift.preset');
    Route::post('/shift/preset/{shiftPreset}/duplicate', [ShiftPresetController::class, 'duplicate'])
        ->name('duplicate.shift.preset');
    Route::patch('/shift/preset/{shiftPreset}/update', [ShiftPresetController::class, 'update'])
        ->name('update.shift.preset');
    Route::get('/shift/template/search', [ShiftPresetController::class, 'search'])->name('shift.template.search');
    Route::post('/shift/{event}/{shiftPreset}/import/preset/', [ShiftPresetController::class, 'import'])
        ->name('shift.preset.import');
    Route::patch('/preset/timeline/update', [PresetTimeLineController::class, 'update'])
        ->name('preset.timeline.update');
    Route::delete('/preset/timeline/{presetTimeLine}/delete', [PresetTimeLineController::class, 'destroy'])
        ->name('preset.delete.timeline.row');
    Route::post('/preset/{shiftPreset}/add', [PresetTimeLineController::class, 'store'])
        ->name('preset.add.timeline.row');


    Route::get('/shift/template/search', [\App\Http\Controllers\ShiftPresetController::class, 'search'])
        ->name('shift.template.search');

    Route::post(
        '/shift/{event}/{shiftPreset}/import/preset/',
        [\App\Http\Controllers\ShiftPresetController::class, 'import']
    )
        ->name('shift.preset.import');

    Route::patch('/preset/timeline/update', [\App\Http\Controllers\PresetTimeLineController::class, 'update'])
        ->name('preset.timeline.update');
    Route::delete(
        '/preset/timeline/{presetTimeLine}/delete',
        [\App\Http\Controllers\PresetTimeLineController::class, 'destroy']
    )
        ->name('preset.delete.timeline.row');
    Route::post('/preset/{shiftPreset}/add', [\App\Http\Controllers\PresetTimeLineController::class, 'store'])
        ->name('preset.add.timeline.row');

    Route::patch('/user/{user}/check/vacation', [\App\Http\Controllers\VacationController::class, 'checkVacation'])
        ->name('user.check.vacation');

    Route::post('/calendar/export/pdf', [ExportPDFController::class, 'createPDF'])->name('calendar.export.pdf');
    Route::get(
        '/calendar/export/pdf/{filename}/download',
        [ExportPDFController::class, 'download']
    )->name('calendar.export.pdf.download');

    Route::post('/shit/multiedit/save', [ShiftController::class, 'saveMultiEdit'])->name('shift.multi.edit.save');
});
