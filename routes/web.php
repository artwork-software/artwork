<?php

use App\Http\Controllers\AppController;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\ArtistResidencyController;
use App\Http\Controllers\AvailabilityController;
use App\Http\Controllers\BudgetAccountManagementController;
use App\Http\Controllers\BudgetGeneralController;
use App\Http\Controllers\BudgetManagementAccountController;
use App\Http\Controllers\BudgetManagementCostUnitController;
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
use App\Http\Controllers\ComponentController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\ContractModuleController;
use App\Http\Controllers\ContractTypeController;
use App\Http\Controllers\CraftController;
use App\Http\Controllers\CraftInventoryItemEventController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\DayServiceController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventStatusController;
use App\Http\Controllers\EventTypeController;
use App\Http\Controllers\EventVerificationController;
use App\Http\Controllers\ExportPDFController;
use App\Http\Controllers\FilterController;
use App\Http\Controllers\FreelancerController;
use App\Http\Controllers\GeneralSettingsController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\IndividualTimeController;
use App\Http\Controllers\MoneySourceCategoryController;
use App\Http\Controllers\MoneySourceController;
use App\Http\Controllers\MoneySourceFileController;
use App\Http\Controllers\MoneySourceReminderController;
use App\Http\Controllers\MoneySourceTaskController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PermissionPresetController;
use App\Http\Controllers\PresetShiftController;
use App\Http\Controllers\PresetTimeLineController;
use App\Http\Controllers\PresetTimelineTimeController;
use App\Http\Controllers\ProjectComponentValueController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectFileController;
use App\Http\Controllers\ProjectManagementBuilderController;
use App\Http\Controllers\ProjectPrintLayoutController;
use App\Http\Controllers\ProjectRoleController;
use App\Http\Controllers\ProjectStatesController;
use App\Http\Controllers\ProjectTabController;
use App\Http\Controllers\ProjectTabSidebarTabController;
use App\Http\Controllers\RoomAttributeController;
use App\Http\Controllers\RoomCategoryController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\RoomFileController;
use App\Http\Controllers\RowCommentController;
use App\Http\Controllers\SageAssignedDataCommentController;
use App\Http\Controllers\SageNotAssignedDataController;
use App\Http\Controllers\SectorController;
use App\Http\Controllers\ServiceProviderContactsController;
use App\Http\Controllers\ServiceProviderController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\ShiftFilterController;
use App\Http\Controllers\ShiftPresetController;
use App\Http\Controllers\ShiftQualificationController;
use App\Http\Controllers\ShiftSettingsController;
use App\Http\Controllers\ShiftTimePresetController;
use App\Http\Controllers\SidebarTabComponentController;
use App\Http\Controllers\SubEventsController;
use App\Http\Controllers\SumCommentController;
use App\Http\Controllers\SumDetailsController;
use App\Http\Controllers\System\FileSettingsController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskTemplateController;
use App\Http\Controllers\TimelinePresetController;
use App\Http\Controllers\ToolSettingsBrandingController;
use App\Http\Controllers\ToolSettingsCommunicationAndLegalController;
use App\Http\Controllers\ToolSettingsInterfacesController;
use App\Http\Controllers\UserCalendarFilterController;
use App\Http\Controllers\UserCalenderAboController;
use App\Http\Controllers\UserCommentedBudgetItemsSettingController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserShiftCalendarAboController;
use App\Http\Controllers\UserShiftCalendarFilterController;
use App\Http\Controllers\VacationController;
use App\Http\Controllers\WorkerController;
use Artwork\Modules\Budget\Http\Controllers\TableColumnOrderController;
use Artwork\Modules\Chat\Http\Controllers\ChatController;
use Artwork\Modules\Event\Http\Controllers\EventListOrCalendarExportController;
use Artwork\Modules\EventProperty\Http\Controller\EventPropertyController;
use Artwork\Modules\GlobalNotification\Http\Controller\GlobalNotificationController;
use Artwork\Modules\Inventory\Http\Controllers\InventoryArticleController;
use Artwork\Modules\Inventory\Http\Controllers\InventoryArticlePropertiesController;
use Artwork\Modules\Inventory\Http\Controllers\InventoryCategoryController;
use Artwork\Modules\Inventory\Http\Controllers\InventoryController;
use Artwork\Modules\Inventory\Http\Controllers\InventorySubCategoryController;
use Artwork\Modules\InventoryManagement\Http\Controllers\CraftInventoryCategoryController;
use Artwork\Modules\InventoryManagement\Http\Controllers\CraftInventoryFilterController;
use Artwork\Modules\InventoryManagement\Http\Controllers\CraftInventoryGroupController;
use Artwork\Modules\InventoryManagement\Http\Controllers\CraftInventoryGroupFolderController;
use Artwork\Modules\InventoryManagement\Http\Controllers\CraftInventoryItemCellController;
use Artwork\Modules\InventoryManagement\Http\Controllers\CraftInventoryItemController;
use Artwork\Modules\InventoryManagement\Http\Controllers\CraftsInventoryColumnController;
use Artwork\Modules\InventoryManagement\Http\Controllers\InventoryManagementExportController;
use Artwork\Modules\InventorySetting\Http\Controllers\InventorySettingsController;
use Artwork\Modules\Invitation\Http\Controller\InvitationController;
use Artwork\Modules\Manufacturer\Http\Controllers\ManufacturerController;
use Artwork\Modules\ModuleSettings\Http\Controller\ModuleSettingsController;
use Artwork\Modules\MoneySource\Http\Middleware\CanEditMoneySource;
use Artwork\Modules\Project\Http\Middleware\CanEditProject;
use Artwork\Modules\Project\Http\Middleware\CanViewProject;
use Artwork\Modules\Room\Http\Middleware\CanViewRoom;
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

    Route::group(['prefix' => 'updates'], function() {
        Route::get('/', [\App\Http\Controllers\NotionController::class, 'index'])
            ->name('notion.index');
    });

    // TOOL SETTING ROUTE
    Route::group(['prefix' => 'tool'], function (): void {
        Route::get('/branding', [ToolSettingsBrandingController::class, 'index'])
            ->name('tool.branding');
        Route::put('/branding', [ToolSettingsBrandingController::class, 'update'])
            ->name('tool.branding.update');
        Route::get('/communication-and-legal', [ToolSettingsCommunicationAndLegalController::class, 'index'])
            ->name('tool.communication-and-legal');
        Route::patch('/communication-and-legal', [ToolSettingsCommunicationAndLegalController::class, 'update'])
            ->name('tool.communication-and-legal.update');
        Route::get('/interfaces', [ToolSettingsInterfacesController::class, 'index'])
            ->name('tool.interfaces');
        Route::post('/interfaces', [ToolSettingsInterfacesController::class, 'createOrUpdate'])
            ->name('tool.interfaces.sage.update');
        Route::post('/interfaces/sage/initialize', [ToolSettingsInterfacesController::class, 'initializeSage'])
            ->name('tool.interfaces.sage.initialize');
        Route::post(
            '/interfaces/sage/initializeSpecificDay',
            [
                ToolSettingsInterfacesController::class,
                'initializeSageSpecificDay'
            ]
        )->name('tool.interfaces.sage.initializeSpecificDay');
        Route::delete('/interfaces/sage/delete', [ToolSettingsInterfacesController::class, 'deleteSageData'])
            ->name('tool.interfaces.sage.delete');
        Route::get('/module-settings', [ModuleSettingsController::class, 'index'])
            ->name('tool.module-settings.index');
        Route::patch('/module-settings', [ModuleSettingsController::class, 'update'])
            ->name('tool.module-settings.update');
        Route::group(['namespace' => 'System', 'prefix' => 'system'], function (): void {
            Route::get('/file-settings', [FileSettingsController::class, 'index'])
                ->name('tool.file-settings.index');
            Route::put('/file-settings', [FileSettingsController::class, 'store'])
                ->name('tool.file-settings.store');
        });
    });

    Route::group(['middleware' => CanEditMoneySource::class], function (): void {
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
    Route::patch(
        '/toggle_calendar_settings_use_project_period',
        [AppController::class, 'toggleCalendarSettingsUseProjectPeriod']
    )->name('user.calendar_settings.toggle_calendar_settings_use_project_period');

    Route::get('/dashboard', [EventController::class, 'showDashboardPage'])->name('dashboard');
    Route::get('/checklist/templates', function () {
        return Inertia::render('ChecklistTemplates/Edit');
    })->name('checklistTemplates.edit');

    //Invitations
    Route::get('/users/invitations/invite', [InvitationController::class, 'invite'])->name('user.invite');
    Route::post('/users/invitations', [InvitationController::class, 'store'])->name('invitations.store');

    Route::patch('/users/{user}/calendar-settings', [UserController::class, 'updateCalendarSettings'])
        ->name('user.calendar_settings.update');

    //Users
    Route::get('/users', [UserController::class, 'index'])->name('users');
    Route::get('/users/addresses', [UserController::class, 'getAddresses'])->name('users.addresses');
    Route::get('/users/search', [UserController::class, 'search'])->name('users.search');
    Route::get('/users/money_source_search', [UserController::class, 'moneySourceSearch'])
        ->name('users.money_source_search');
    Route::get('/users/{user}/info', [UserController::class, 'editUserInfo'])->name('user.edit.info');
    Route::get('/users/{user}/shiftplan', [UserController::class, 'editUserShiftPlan'])->name('user.edit.shiftplan');
    Route::get('/users/{user}/terms', [UserController::class, 'editUserTerms'])->name('user.edit.terms');
    Route::get('/users/{user}/permissions', [UserController::class, 'editUserPermissions'])
        ->name('user.edit.permissions');
    Route::get('/users/{user}/workProfile', [UserController::class, 'editUserWorkProfile'])->can('can manage workers')
        ->name('user.edit.workProfile');
    Route::patch('/users/{user}/edit', [UserController::class, 'updateUserDetails'])->name('user.update');
    Route::patch(
        '/users/{user}/permissions',
        [
            UserController::class,
            'updateUserPermissionsAndRoles'
        ]
    )->name('user.update.permissions-and-roles');
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
    Route::patch('/users/{user}/shift-qualification', [UserController::class, 'updateShiftQualification'])
        ->name('user.update.shift-qualification');
    Route::patch('/users/{user}/workProfile', [UserController::class, 'updateWorkProfile'])
        ->name('user.update.workProfile');
    Route::patch('/users/{user}/assignCraft', [UserController::class, 'assignCraft'])->name('user.assign.craft');
    Route::delete('/users/{user}/removeCraft/{craft}', [UserController::class, 'removeCraft'])
        ->name('user.remove.craft');
    //user.sidebar.update
    Route::patch('/users/{user}/sidebar/update', [UserController::class, 'updateSidebar'])
        ->name('user.sidebar.update');
    //user.checklist.style
    Route::patch('/users/{user}/checklist/style', [UserController::class, 'updateChecklistStyle'])
        ->name('user.checklist.style');

    //Departments
    Route::get('/departments', [DepartmentController::class, 'index'])->name('departments');
    Route::get('/departments/search', [DepartmentController::class, 'search'])->name('departments.search');
    Route::post('/departments', [DepartmentController::class, 'store'])->name('departments.store');
    Route::get('/departments/{department}', [DepartmentController::class, 'show'])->name('departments.show');
    Route::get('/departments/{department}/edit', [DepartmentController::class, 'edit'])->name('departments.profile');
    Route::patch('/departments/{department}', [DepartmentController::class, 'update'])->name('departments.edit');
    Route::patch('/departments/{department}/remove/members', [DepartmentController::class, 'removeAllMembers'])
        ->name('departments.remove.members');
    Route::delete('/departments/{department}', [DepartmentController::class, 'destroy']);

    //Projects
    Route::post('/projects/{project}/pin', [ProjectController::class, 'pin'])->name('project.pin');
    Route::get('/projects', [ProjectController::class, 'index'])->name('projects');
    Route::get('/projects/search', [ProjectController::class, 'search'])->name('projects.search');
    Route::get('/projects/search/single', [ProjectController::class, 'searchProjectsWithoutGroup'])
        ->name('projects.search.single');
    Route::get('/trashedProjects', [ProjectController::class, 'getTrashed'])->name('projects.trashed');
    Route::get('/projects/users_departments/search', [ProjectController::class, 'searchDepartmentsAndUsers'])
        ->name('users_departments.search');
    Route::get('/projects/create', [ProjectController::class, 'create'])->name('projects.create');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::post('/projects/appleFilter', [ProjectController::class, 'saveProjectManagementFilter'])
        ->name('projects.filter');
    Route::get(
        '/projects/export/budget/{startBudgetDeadline}/{endBudgetDeadline}',
        [ProjectController::class, 'projectsBudgetByBudgetDeadlineExport']
    )->name('projects.export.budgetByBudgetDeadline');
    Route::post('/projects/{project}/updateKeyVisual', [ProjectController::class, 'updateKeyVisual'])
        ->name('projects_key_visual.update');
    Route::post('/projects/{project}/duplicate', [ProjectController::class, 'duplicate'])->name('projects.duplicate');
    Route::get('/projects/{project}/edit', [ProjectController::class, 'edit'])
        ->middleware(CanEditProject::class);

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
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');

    Route::patch('/projects/{project}/team', [ProjectController::class, 'updateTeam'])
        ->name('projects.update_team');
    Route::get('/projects/{project}/export/budget', [ProjectController::class, 'projectBudgetExport'])
        ->name('projects.export.budget');
    Route::post('/project/{project}/bulk/event/store', [EventController::class, 'bulkProjectEventStore'])
        ->name('events.bulk.store');

    //ProjectTabs
    Route::get('/projects/{project}/tab/{projectTab}', [ProjectController::class, 'projectTab'])
        ->name('projects.tab')
        ->middleware(CanViewProject::class);

    //ProjectFiles
    Route::post('/projects/{project}/files', [ProjectFileController::class, 'store'])->name('project_files.store');
    Route::post('/project_files/{project_file}', [ProjectFileController::class, 'update'])
        ->name('project_files.update');
    Route::get('/project_files/{project_file}', [ProjectFileController::class, 'download'])->name('download_file');
    Route::delete('/project_files/{project_file}', [ProjectFileController::class, 'destroy'])
        ->name('project_files.destroy');
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
    Route::post('/checklists/{checklist}/duplicate', [ChecklistController::class, 'duplicate'])
        ->name('checklists.duplicate');
    Route::get('/checklists/{checklist}/edit', [ChecklistController::class, 'edit']);
    Route::patch('/checklists/{checklist}', [ChecklistController::class, 'update'])->name('checklists.update');
    Route::delete('/checklists/{checklist}', [ChecklistController::class, 'destroy'])->name('checklist.destroy');

    // route change.task.checklist
    Route::patch('/checklists/{checklist}/change/task/{task}', [TaskController::class, 'changeTaskChecklist'])
        ->name('checklists.change.task');


    //checklist.done.all.tasks
    Route::patch('/checklists/{checklist}/doneOrUndone/all/tasks', [ChecklistController::class, 'doneOrUndoneAllTasks'])
        ->name('checklists.doneOrUndone.all.tasks');

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
    Route::delete('/checklist_templates/{checklist_template}', [ChecklistTemplateController::class, 'destroy'])
        ->name('checklist_templates.destroy');
    // checklist_templates.duplicate
    Route::post(
        '/checklist_templates/{checklistTemplate}/duplicate',
        [ChecklistTemplateController::class, 'duplicate']
    )
        ->name('checklist_templates.duplicate');

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
    Route::put('/tasks/order', [TaskController::class, 'updateOrder'])->name('tasks.order');
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
    Route::patch('/tasks/{task}/done', [TaskController::class, 'updateDoneStatus'])->name('tasks.done');

    //Categories
    Route::get('/settings/projects', [CategoryController::class, 'index'])->name('project.settings');
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::patch('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);
    Route::patch('/categories/{category}/restore', [CategoryController::class, 'restore']);
    Route::delete('/categories/{id}/force', [CategoryController::class, 'forceDelete'])->name('categories.force');
    Route::patch('/project/create/settings', [ProjectController::class, 'updateSettings'])
        ->name('project_settings.update');

    //Genres
    Route::post('/genres', [GenreController::class, 'store'])->name('genres.store');
    Route::patch('/genres/{genre}', [GenreController::class, 'update'])->name('genres.update');
    Route::delete('/genres/{genre}', [GenreController::class, 'destroy']);
    Route::patch('/genres/{genre}/restore', [GenreController::class, 'restore']);
    Route::delete('/genres/{id}/force', [GenreController::class, 'forceDelete'])->name('genres.force');

    //Sectors
    Route::post('/sectors', [SectorController::class, 'store'])->name('sectors.store');
    Route::patch('/sectors/{sector}', [SectorController::class, 'update'])->name('sectors.update');
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
    Route::get('/trashedAreas', [AreaController::class, 'getTrashed'])->name('areas.trashed');
    Route::post('/areas', [AreaController::class, 'store'])->name('areas.store');
    Route::post('/areas/{area}/duplicate', [AreaController::class, 'duplicate'])->name('areas.duplicate');
    Route::patch('/areas/{area}', [AreaController::class, 'update'])->name('areas.update');
    Route::delete('/areas/{area}', [AreaController::class, 'destroy']);

    //Trash
    Route::delete('/areas/{id}/force', [AreaController::class, 'forceDelete'])->name('areas.force');
    Route::patch('/areas/{id}/restore', [AreaController::class, 'restore'])->name('areas.restore');

    Route::get('/rooms/move', [RoomController::class, 'getMoveRooms'])->name('rooms.move');
    Route::post('/rooms/order/new', [RoomController::class, 'updateOrderNew'])->name('rooms.order.new');

    //Rooms
    Route::post('/rooms', [RoomController::class, 'store'])->name('rooms.store');
    Route::get('/trashedRooms', [RoomController::class, 'getTrashed'])->name('rooms.trashed');
    Route::get('/rooms/free', [RoomController::class, 'getAllDayFree'])->name('rooms.free');
    Route::post('/rooms/{room}/duplicate', [RoomController::class, 'duplicate'])->name('rooms.duplicate');
    Route::get('/rooms/{room}', [RoomController::class, 'show'])
        ->name('rooms.show')->middleware(CanViewRoom::class);
    Route::patch('/rooms/{room}', [RoomController::class, 'update'])->name('rooms.update');
    Route::patch('/rooms/{room}/users', [RoomController::class, 'updateRoomUsers'])->name('room.users.update');
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
    Route::post('/filters', [FilterController::class, 'store'])->name('filter.store');
    Route::delete('/filters/{filter}', [FilterController::class, 'destroy'])->name('filter.destroy');
    Route::post('/filters/{filter}/{user}', [FilterController::class, 'activate'])->name('filter.activate');

    //Shift Filters
    Route::get('/shifts/filters', [ShiftFilterController::class, 'index']);
    Route::post('/shifts/filters', [ShiftFilterController::class, 'store']);
    Route::delete('/shifts/filters/{filter}', [ShiftFilterController::class, 'destroy']);

    //Event Views
    Route::get('/calendar/view', [EventController::class, 'viewEventIndex'])->name('events');
    Route::get('/calendar/room/events', [EventController::class, 'getEventsForRoomsByDaysAndProject'])
        ->name('events.for-rooms-by-days-and-project');
    Route::get('/events/requests', [EventController::class, 'viewRequestIndex'])->name('events.requests');
    Route::get('/trashedEvents', [EventController::class, 'getTrashed'])->name('events.trashed');

    // Event Api
    Route::post('/events', [EventController::class, 'storeEvent'])->name('events.store');
    Route::put('/events/{event}', [EventController::class, 'updateEvent'])->name('events.update');
    Route::patch('/events/{event}/description', [EventController::class, 'updateDescription'])
        ->name('event.update.description');
    Route::patch('/events/{event}/single/bulk', [EventController::class, 'updateSingleBulkEvent'])
        ->name('event.update.single.bulk');
    Route::post('/events/{project}/single/bulk/create', [EventController::class, 'createSingleBulkEvent'])
        ->name('event.store.bulk.single');

    // events.bulk-multi-edit
    Route::post('/events/bulk/multi/edit', [EventController::class, 'bulkMultiEditEvent'])
        ->name('events.bulk-multi-edit');

    // event.bulk.delete
    Route::delete('/events/bulk/delete', [EventController::class, 'bulkDeleteEvent'])
        ->name('event.bulk.multi-edit.delete');

    Route::delete('/events/{event}', [EventController::class, 'destroy'])->name('events.delete');
    Route::delete('/events/{event}/bulk', [EventController::class, 'destroyWithoutReturn'])->name('event.bulk.delete');
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
    Route::get('/shifts/view', [EventController::class, 'viewShiftPlan'])
        ->name('shifts.plan')
        ->can('can view shift plan');


    Route::post('/shift/delete/calendar/cell', [ShiftController::class, 'deleteCalendarCell'])
        ->name('multi-edit.calendar.cell.delete');

    Route::group(['prefix' => 'shift-plan'], function (): void {
        // POST event.shift.store.without.event
        Route::post('/event/shift/store/without/event', [ShiftController::class, 'storeShiftWithoutEvent'])
            ->name('event.shift.store.without.event');
    });


    Route::get('/shifts/view/events-and-workers', [EventController::class, 'getEventsForRoomsByDaysWithUser'])
        ->name('shifts.events.for-rooms-by-days-and-project');
    Route::get('/shifts/view/events-and-no-workers', [EventController::class, 'getEventsForRoomsByDaysWithoutUser'])
        ->name('shifts.events.for-rooms-by-days-and-project-no-workers');
    Route::get('/shifts/presets', [ShiftPresetController::class, 'index'])->name('shifts.presets');
    Route::post('/shift/{shiftPreset}/preset/store', [PresetShiftController::class, 'store'])
        ->name('shift.preset.store');
    Route::post('/shifts/commit', [EventController::class, 'commitShifts'])->name('shifts.commit');

    // patch shifts.qualifications.add
    Route::patch('/shifts/{shift}/qualifications/add', [ShiftQualificationController::class, 'updateValue'])
        ->name('shifts.qualifications.add');

    // shift.plan.user.cell.update
    Route::post('/shiftplan/user/cell/update', [ShiftController::class, 'updateUserCell'])
        ->name('shift.plan.user.cell.update');

    // multi-edit.cell.delete
    Route::post('/shiftplan/multi/edit/cell/delete', [ShiftController::class, 'deleteMultiEditCell'])
        ->name('multi-edit.cell.delete');

    // event.shift.store.multi.add
    Route::post('/event/shift/store/multi/add', [ShiftController::class, 'storeShiftMultiAdd'])
        ->name('event.shift.store.multi.add');



    Route::group(['prefix' => 'settings'], function (): void {
        Route::get('/event_types', [EventTypeController::class, 'index'])
            ->name('event_types.management')
            ->can('change event settings');
        Route::get('/holiday', [HolidayController::class, 'index'])
            ->name('holiday.management');
        Route::post('/holiday/api', [HolidayController::class, 'create'])
            ->name('holiday.api.call');
        Route::post('/holiday/store', [HolidayController::class, 'store'])
            ->name('holiday.store');
        Route::delete('/holiday/{holiday}', [HolidayController::class, 'destroy'])
            ->name('holiday.delete');
        Route::patch('/holiday/{holiday}', [HolidayController::class, 'update'])
            ->name('holiday.update');


        Route::group(['prefix' => 'event-status'], function (): void {
            Route::get('/', [EventStatusController::class, 'index'])
                ->name('event_status.management');

            // event_status.update_settings
            Route::patch('/update/settings', [EventStatusController::class, 'updateSettings'])
                ->name('event_status.update_settings');

            // event_status.reorder
            Route::patch('/reorder', [EventStatusController::class, 'reorder'])
                ->name('event_status.reorder');

            // event_status.store
            Route::post('/create', [EventStatusController::class, 'store'])
                ->name('event_status.store');

            //event_status.update
            Route::patch('/{eventStatus}', [EventStatusController::class, 'update'])
                ->name('event_status.update');

            //event_status.delete
            Route::delete('/{eventStatus}', [EventStatusController::class, 'destroy'])
                ->name('event_status.delete');
        });

        Route::group(['prefix' => 'event_properties'], function (): void {
            Route::get('/', [EventPropertyController::class, 'index'])
                ->name('event_settings.event_properties.index');
            Route::post('/', [EventPropertyController::class, 'store'])
                ->name('event_settings.event_properties.store');
            Route::patch('/{eventProperty}', [EventPropertyController::class, 'update'])
                ->name('event_settings.event_properties.update');
            Route::delete('/{eventProperty}', [EventPropertyController::class, 'destroy'])
                ->name('event_settings.event_properties.delete');
        });
    });

    //EventTypes

    Route::post('/event_types', [EventTypeController::class, 'store'])->name('event_types.store');
    Route::get('/event_types/{event_type}', [EventTypeController::class, 'show'])->name('event_types.show');
    Route::patch('/event_types/{event_type}', [EventTypeController::class, 'update'])->name('event_types.update');
    Route::delete('/event_types/{event_type}', [EventTypeController::class, 'destroy']);

    // notification
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/collision/room', [RoomController::class, 'collisionsCount'])->name('collisions.room');
    Route::patch('/notifications', [NotificationController::class, 'setReadAt'])->name('notifications.setReadAt');
    Route::patch('/notifications/all', [NotificationController::class, 'setOnReadAll'])
        ->name('notifications.setReadAtAll');
    Route::patch('/user/settings/group', [NotificationController::class, 'toggleGroup'])->name('notifications.group');
    Route::patch('/user/settings/{setting}', [NotificationController::class, 'updateSetting'])
        ->name('notifications.settings');
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy'])->name('notifications.delete');
    Route::post('/notifications/{notificationKey}', [EventController::class, 'deleteOldNotifications'])
        ->name('event.notification.delete');

    //globalNotification
    Route::put('/globalNotification/create', [GlobalNotificationController::class, 'store'])
        ->name('global_notification.store');
    Route::put('/globalNotification/{globalNotification}', [GlobalNotificationController::class, 'update'])
        ->name('global_notification.update');
    Route::delete('/globalNotification/{globalNotification}', [GlobalNotificationController::class, 'destroy'])
        ->name('global_notification.destroy');

    // Money Sources
    Route::get('/settings/money_sources', [MoneySourceController::class, 'showSettings'])
        ->name('money_sources.settings');
    Route::get('/money_sources/search/money_source', [MoneySourceController::class, 'search'])
        ->name('money_sources.search');
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
    Route::get('/contracts/view', [ContractController::class, 'index'])->name('contracts.index');
    Route::post('/projects/{project}/contracts', [ContractController::class, 'store'])->name('contracts.store');
    Route::get('/contracts/{contract}', [ContractController::class, 'show'])->name('contracts.show');
    Route::get('/contracts/{contract}/download', [ContractController::class, 'download'])->name('contracts.download');
    Route::patch('/contracts/{contract}', [ContractController::class, 'update'])->name('contracts.update');
    Route::delete('/contracts/{contract}', [ContractController::class, 'destroy'])->name('contract.delete');
    Route::post('/contract', [ContractController::class, 'storeFile'])->name('contracts.store.file');

    //ContractModules
    Route::get('/contract_modules', [ContractModuleController::class, 'index'])->name('contracts.module.management');
    Route::post('/contract_modules/store', [ContractModuleController::class, 'store'])->name('contracts.module.store');
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
    Route::patch(
        '/user/{user}/worker/shift-plan/filters/update',
        [UserShiftCalendarFilterController::class, 'updateUserWorkerShiftPlanFilters']
    )->name('update.user.worker.shift-plan.filters.update');

    //user.update.zoom_factor
    Route::patch('/user/{user}/update/zoom_factor', [UserController::class, 'updateZoomFactor'])
        ->name('user.update.zoom_factor');

    // user.update.at_a_glance
    Route::patch('/user/{user}/update/at_a_glance', [UserController::class, 'updateAtAGlance'])
        ->name('user.update.at_a_glance');

    Route::patch('/user/{user}/update/bulk/sort_id', [UserController::class, 'updateBulkSortId'])
        ->name('user.update_bulk_sort_id');

    // user.update.daily_view
    Route::patch('/user/{user}/update/daily_view', [UserController::class, 'updateDailyView'])
        ->name('user.update.daily_view');

    Route::patch('/user/{user}/update/bulk-column-size', [UserController::class, 'updateBulkColumnSize'])
        ->name('user.bulk-column-size.update');

    Route::resource(
        'user.commentedBudgetItemsSettings',
        UserCommentedBudgetItemsSettingController::class
    )->only(['store', 'update']);

    Route::group(['prefix' => 'timeline-preset'], function (): void {
        //shifts.timeline-presets.index
        Route::get('/', [TimelinePresetController::class, 'index'])
            ->name('shifts.timeline-presets.index');

        // post: timeline-presets.store
        Route::post('/store', [TimelinePresetController::class, 'store'])
            ->name('timeline-presets.store');

        // patch timeline-presets.update
        Route::patch('/{shiftPresetTimeline}', [TimelinePresetController::class, 'update'])
            ->name('timeline-presets.update');

        // POST: timeline-preset.copy
        Route::post('/{shiftPresetTimeline}/duplicate', [TimelinePresetController::class, 'duplicate'])
            ->name('timeline-preset.duplicate');

        // delete timeline-preset.destroy
        Route::delete('/{shiftPresetTimeline}', [TimelinePresetController::class, 'destroy'])
            ->name('timeline-preset.destroy');

        // timeline-preset.time.delete
        Route::delete('/{presetTimelineTime}/time/destroy', [PresetTimelineTimeController::class, 'destroy'])
            ->name('timeline-preset.time.destroy');

        // post timeline-preset.import
        Route::post('/import/{event}/{shiftPresetTimeline}', [ShiftController::class, 'importTimelinePreset'])
            ->name('timeline-preset.import');

        // post timeline-preset.store
        Route::post('/{event}/timeline-preset/store', [ShiftController::class, 'storeTimelinePresetFormEvent'])
            ->name('timeline-preset.store.form.event');
    });

    // Project Routes
    Route::group(['prefix' => 'project'], function (): void {

        Route::group(['prefix' => 'artist-residencies'], function (): void {
            // store
            Route::post('/{project}/artist-residencies', [ArtistResidencyController::class, 'store'])
                ->name('artist-residencies.store');

            // patch
            Route::patch('/{artistResidency}', [ArtistResidencyController::class, 'update'])
                ->name('artist-residencies.update');

            // artist_residencies.duplicate
            Route::post('/{artistResidency}/duplicate', [ArtistResidencyController::class, 'duplicate'])
                ->name('artist_residencies.duplicate');
            //artist_residencies.destroy
            Route::delete('/{artistResidency}', [ArtistResidencyController::class, 'destroy'])
                ->name('artist-residency.destroy');

            //artist-residencies.export-pdf
            Route::post('/{project}/{language}/export-pdf', [ArtistResidencyController::class, 'exportPdf'])
                ->name('artist-residencies.export-pdf');
            //artist-residencies.export-excel
            Route::get('/{project}/{language}/export-excel', [ArtistResidencyController::class, 'exportExcel'])
                ->name('artist-residencies.export-excel');

            //artist-residency.export.pdf.download
            Route::get('/export-pdf/download/{filename}', [ArtistResidencyController::class, 'exportPdfDownload'])
                ->name('artist-residency.export.pdf.download');
        });


        // GET
        Route::get('/user/search', [ProjectController::class, 'projectUserSearch'])->name('project.user.search');
        Route::get('/{project}/download/keyVisual', [ProjectController::class, 'downloadKeyVisual'])
            ->name('project.download.keyVisual');

        // POST
        Route::post('/{shift}/assign', [ShiftController::class, 'assignToShift'])
            ->name('shift.assignUserByType');

        Route::post('/timeline/add/{event}', [ProjectController::class, 'addTimeLineRow'])->name('add.timeline.row');
        Route::post('/timeline/update/magic/{event}', [ShiftController::class, 'updateTimeLine'])
            ->name('edit.timeline.event');
        Route::post('/timeline/add/magic/{event}', [ShiftController::class, 'addTimeLine'])
            ->name('create.timeline.event');
        Route::post('/{event}/shift/store', [ShiftController::class, 'store'])->name('event.shift.store');
        Route::post('/sums/money-source', [SumDetailsController::class, 'store'])
            ->name('project.sum.money.source.store');

        // PATCH
        Route::patch('/timeline/{timeline}/update', [ProjectController::class, 'updateTimeline'])
            ->name('update.timeline');
        Route::patch('/shifts/commit', [ShiftController::class, 'updateCommitments'])->name('update.shift.commitment');
        Route::patch('/{shift}/update', [ShiftController::class, 'updateShift'])->name('event.shift.update');

        // shifts.updateTime
        Route::patch('/{shift}/update/time', [ShiftController::class, 'updateTime'])
            ->name('event.shift.update.updateTime');
        Route::patch('/{shift}/update/description', [ShiftController::class, 'updateDescription'])
            ->name('event.shift.update.updateDescription');
        Route::patch('/sums/money-source/{sumMoneySource}', [SumDetailsController::class, 'update'])
            ->name('project.sum.money.source.update');
        Route::patch('/{presetShift}/preset/update/description', [ShiftPresetController::class, 'updateDescription'])
            ->name('preset.shift.update.updateDescription');
        // DELETE
        Route::delete('/{shift}/destroy', [ShiftController::class, 'destroy'])->name('shifts.destroy');
        Route::delete('/timeline/delete/{timeline}', [ProjectController::class, 'deleteTimeLineRow'])
            ->name('delete.timeline.row');
        Route::delete('/sums/money-source/{sumMoneySource}', [SumDetailsController::class, 'destroy'])
            ->name('project.sum.money.source.destroy');
        Route::delete('/group/{project}/{projectGroup}', [ProjectController::class, 'deleteProjectFromGroup'])
            ->name('projects.group.delete');
        Route::post('/group/{projectGroup}/add/projects', [ProjectController::class, 'addProjectsToGroup'])
            ->name('project-group.add-projects');
        Route::delete('/{project}/delete/keyVisual', [ProjectController::class, 'deleteKeyVisual'])
            ->name('project.delete.keyVisual');
        Route::delete('/removeFromShift/{usersPivotId}/type/{userType}', [ShiftController::class, 'removeFromShift'])
            ->name('shift.removeUserByType');
        Route::delete('/removeAllShiftUsers/{shift}', [ShiftController::class, 'removeAllShiftUsers'])
            ->name('shift.removeAllUsers');

        Route::group(['prefix' => 'budget'], function (): void {
            // GET
            Route::get('/cell/comments', [CellCommentsController::class, 'get'])
                ->name('project.budget.cell.comment.get');

            // patch project.budget.column.update.relevant
            Route::patch('/column/{column}/update/relevant', [BudgetGeneralController::class, 'updateColumnRelevant'])
                ->name('project.budget.column.update.relevant');

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

            // drop sage data
            Route::post('/drop/sage', [ProjectController::class, 'dropSageData'])
                ->name('project.budget.drop.sage');

            // move sage data on cell
            Route::post(
                '/move/sage/{sageNotAssignedData}/{columnCell}',
                [SageNotAssignedDataController::class, 'moveSageData']
            )->name('project.budget.move.sage');

            // project.budget.move.sage.row
            Route::post(
                '/move/sage/row/{columnCell}/{movedColumn}',
                [BudgetGeneralController::class, 'moveSageDataRow']
            )->name('project.budget.move.sage.row');

            // project.budget.move.sage.row
            Route::post(
                '/move/sage/row/to/row/{table_id}/{sub_position_id}/{positionBefore}/{columnCell}',
                [BudgetGeneralController::class, 'moveSageDataRowToNewRow']
            )->name('project.budget.move.sage.to.row');

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
            Route::patch('/columns/update-orders', [TableColumnOrderController::class, 'updateTableColumnOrders'])
                ->name('project.budget.updateTableColumnOrders');

            // DELETE
            Route::delete('/sub-position-row/{subPositionRow}', [ProjectController::class, 'deleteRow'])
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

            Route::get('/sageNotAssignedData/trashed', [SageNotAssignedDataController::class, 'getTrashed'])
                ->name('sageNotAssignedData.trashed');

            Route::delete(
                '/sageNotAssignedData/{sageNotAssignedData}',
                [
                    SageNotAssignedDataController::class, 'destroy'
                ]
            )
                ->name('sageNotAssignedData.destroy');
            Route::delete(
                '/sageNotAssignedData/{sageNotAssignedData}/forceDelete',
                [
                    SageNotAssignedDataController::class, 'forceDelete'
                ]
            )
                ->name('sageNotAssignedData.forceDelete')
                ->withTrashed();
            Route::patch(
                '/sageNotAssignedData/{sageNotAssignedData}/restore',
                [
                    SageNotAssignedDataController::class, 'restore'
                ]
            )
                ->name('sageNotAssignedData.restore')
                ->withTrashed();

            Route::resource('sageAssignedDataComments', SageAssignedDataCommentController::class)
                ->only(['store', 'destroy']);
        });
    });

    Route::patch('/project/{project}/budget/reset', [ProjectController::class, 'resetTable'])
        ->name('project.budget.reset.table');

    // Budget Settings
    Route::group(['prefix' => 'budget-settings'], function (): void {
        Route::get('/general', [BudgetGeneralController::class, 'index'])
            ->name('budget-settings.general');
        Route::patch('/general/{budgetColumnSetting}', [BudgetGeneralController::class, 'update'])
            ->name('budget-settings.general.update');

        Route::get('/account-management', [BudgetAccountManagementController::class, 'index'])
            ->name('budget-settings.account-management');
        Route::patch(
            '/account-management/updateGlobalSetting',
            [
                GeneralSettingsController::class,
                'updateBudgetAccountManagementGlobal'
            ]
        )->name('budget-settings.account-management.updateBudgetAccountManagementGlobal');
        Route::post(
            '/account-management/account',
            [
                BudgetManagementAccountController::class,
                'store'
            ]
        )->name('budget-settings.account-management.store-account');
        Route::patch(
            '/account-management/account/{budgetManagementAccount}',
            [
                BudgetManagementAccountController::class,
                'update'
            ]
        )->name('budget-settings.account-management.update-account');
        Route::delete(
            '/account-management/account/{budgetManagementAccount}',
            [
                BudgetManagementAccountController::class,
                'destroy'
            ]
        )->name('budget-settings.account-management.destroy-account');
        Route::post(
            '/account-management/cost-unit',
            [
                BudgetManagementCostUnitController::class,
                'store'
            ]
        )->name('budget-settings.account-management.store-cost-unit');
        Route::patch(
            '/account-management/cost-unit/{budgetManagementCostUnit}',
            [
                BudgetManagementCostUnitController::class,
                'update'
            ]
        )->name('budget-settings.account-management.update-cost-unit');
        Route::delete(
            '/account-management/cost-unit/{budgetManagementCostUnit}',
            [
                BudgetManagementCostUnitController::class,
                'destroy'
            ]
        )->name('budget-settings.account-management.destroy-cost-unit');
        Route::get(
            '/account-management/accounts/trashed',
            [
                BudgetManagementAccountController::class,
                'indexTrash'
            ]
        )->name('budget-settings.account-management.trash-accounts');
        Route::patch(
            '/account-management/accounts/trashed/{budgetManagementAccount}',
            [
                BudgetManagementAccountController::class,
                'restore'
            ]
        )->withTrashed()->name('budget-settings.account-management.trash-accounts.restore');
        Route::delete(
            '/account-management/accounts/trashed/{budgetManagementAccount}',
            [
                BudgetManagementAccountController::class,
                'forceDelete'
            ]
        )->withTrashed()->name('budget-settings.account-management.trash-accounts.forceDelete');
        Route::get(
            '/account-management/cost-units/trashed',
            [
                BudgetManagementCostUnitController::class,
                'indexTrash'
            ]
        )->name('budget-settings.account-management.trash-cost-units');
        Route::patch(
            '/account-management/cost-units/trashed/{budgetManagementCostUnit}',
            [
                BudgetManagementCostUnitController::class,
                'restore'
            ]
        )->withTrashed()->name('budget-settings.account-management.trash-cost-units.restore');
        Route::delete(
            '/account-management/cost-units/trashed/{budgetManagementCostUnit}',
            [
                BudgetManagementCostUnitController::class,
                'forceDelete'
            ]
        )->withTrashed()->name('budget-settings.account-management.trash-cost-units.forceDelete');
        Route::get(
            '/account-management/accounts/search',
            [
                BudgetManagementAccountController::class,
                'search'
            ]
        )->name('budget-settings.account-management.search-accounts');
        Route::get(
            '/account-management/cost-units/search',
            [
                BudgetManagementCostUnitController::class,
                'search'
            ]
        )->name('budget-settings.account-management.search-cost-units');

        Route::get('/templates', [BudgetTemplateController::class, 'index'])
            ->name('budget-settings.templates')
            ->can('view budget templates');
    });

    Route::post('/project/{project}/copyright/update', [ProjectController::class, 'updateCopyright'])
        ->name('project.copyright.update');

    // ContractTypes
    Route::get('/contract_types', [ContractTypeController::class, 'index'])->name('contract_types.index');
    Route::post('/contract_types', [ContractTypeController::class, 'store'])->name('contract_types.store');
    Route::delete('/contract_types/{contract_type}', [ContractTypeController::class, 'destroy'])
        ->name('contract_types.delete');
    Route::patch('/contract_types/{contract_type}/restore', [ContractTypeController::class, 'restore'])
        ->name('contract_types.restore');
    Route::delete('/contract_types/{id}/force', [ContractTypeController::class, 'forceDelete'])
        ->name('contract_types.force');
    Route::patch('/contract_types/{contract_type}/update', [ContractTypeController::class, 'update'])
        ->name('contract_types.update');

    // CompanyTypes
    Route::get('/company_types', [CompanyTypeController::class, 'index'])->name('company_types.index');
    Route::post('/company_types', [CompanyTypeController::class, 'store'])->name('company_types.store');
    Route::delete('/company_types/{company_type}', [CompanyTypeController::class, 'destroy'])
        ->name('company_types.delete');
    Route::patch('/company_types/{company_type}/restore', [CompanyTypeController::class, 'restore'])
        ->name('company_types.restore');
    Route::delete('/company_types/{id}/force', [CompanyTypeController::class, 'forceDelete'])
        ->name('company_types.force');
    Route::patch('/company_types/{company_type}/update', [CompanyTypeController::class, 'update'])
        ->name('company_types.update');

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
    Route::patch(
        '/collecting_societies/{collecting_society}/update',
        [CollectingSocietyController::class, 'update']
    )->name('collecting_societies.update');
    // Currencies
    Route::get('/currencies', [CurrencyController::class, 'index'])->name('currencies.index');
    Route::post('/currencies', [CurrencyController::class, 'store'])->name('currencies.store');
    Route::delete('/currencies/{currency}', [CurrencyController::class, 'destroy'])->name('currencies.delete');
    Route::patch('/currencies/{currency}/restore', [CurrencyController::class, 'restore'])->name('currencies.restore');
    Route::patch('/currencies/{currency}/update', [CurrencyController::class, 'update'])->name('currencies.update');
    Route::delete('/currencies/{id}/force', [CurrencyController::class, 'forceDelete'])->name('currencies.force');

    // Project States
    Route::post('/state', [ProjectStatesController::class, 'store'])->name('state.store');
    Route::patch('/project/{project}/state', [ProjectController::class, 'updateProjectState'])
        ->name('update.project.state');
    Route::delete('/state/{projectStates}', [ProjectStatesController::class, 'destroy'])->name('state.delete');
    Route::patch('/states/{state}/restore', [ProjectStatesController::class, 'restore'])->name('state.restore');
    Route::patch('/states/{projectStates}/update', [ProjectStatesController::class, 'update'])->name('state.update');
    Route::delete('/states/{id}/force', [ProjectStatesController::class, 'forceDelete'])->name('state.force');

    // Project Settings
    Route::get('/trashedProjects/settings', [ProjectController::class, 'getTrashedSettings'])
        ->name('projects.settings.trashed');

    // Sub Event
    Route::post('/sub-event/add', [SubEventsController::class, 'store'])->name('subEvent.add');
    Route::delete('/sub-event/{subEvents}', [SubEventsController::class, 'destroy'])->name('subEvent.delete');
    Route::patch('/sub-event/{subEvents}', [SubEventsController::class, 'update'])->name('subEvent.update');

    // MultiEdit
    Route::patch('/multi-edit', [EventController::class, 'updateMultiEdit'])->name('multi-edit.save');
    Route::patch('/multi-duplicate', [EventController::class, 'updateMultiDuplicate'])->name('multi-duplicate.save');
    Route::post('/multi-edit', [EventController::class, 'deleteMultiEdit'])->name('multi-edit.delete');

    // Calendar
    Route::get(
        '/calendars/filters',
        [CalendarController::class, 'getCalendarFilterDefinitions']
    )->name('calendar.filters');

    // Freelancer
    Route::get('/freelancer/{freelancer}', [FreelancerController::class, 'show'])->name('freelancer.show');
    Route::patch('/freelancer/update/{freelancer}', [FreelancerController::class, 'update'])->name('freelancer.update');
    Route::post('/freelancer/profile-image/{freelancer}', [FreelancerController::class, 'updateProfileImage'])
        ->name('freelancer.change.profile-image');
    Route::post('freelancer/add', [FreelancerController::class, 'store'])->name('freelancer.add');
    Route::delete('freelancer/{freelancer}', [FreelancerController::class, 'destroy'])->name('freelancer.destroy');
    Route::patch('/freelancer/{freelancer}/workProfile', [FreelancerController::class, 'updateWorkProfile'])
        ->can('can manage workers')
        ->name('freelancer.update.workProfile');
    Route::patch('/freelancer/{freelancer}/terms', [FreelancerController::class, 'updateTerms'])
        ->name('freelancer.update.terms');
    Route::patch(
        '/freelancer/{freelancer}/updateCraftSettings',
        [FreelancerController::class, 'updateCraftSettings']
    )->name('freelancer.update.craftSettings');
    Route::patch(
        '/freelancer/{freelancer}/shift-qualification',
        [FreelancerController::class, 'updateShiftQualification']
    )->name('freelancer.update.shift-qualification');
    Route::patch(
        '/freelancer/{freelancer}/assignCraft',
        [FreelancerController::class, 'assignCraft']
    )->name('freelancer.assign.craft');
    Route::delete(
        '/freelancer/{freelancer}/removeCraft/{craft}',
        [FreelancerController::class, 'removeCraft']
    )->name('freelancer.remove.craft');

    // Vacation

    Route::post('/freelancer/vacation/{freelancer}/add', [VacationController::class, 'storeFreelancerVacation'])
        ->name('freelancer.vacation.add');
    Route::patch('/freelancer/vacation/{freelancerVacation}/update', [VacationController::class, 'update'])
        ->name('freelancer.vacation.update');
    Route::delete('/freelancer/vacation/{freelancerVacation}/delete', [VacationController::class, 'destroy'])
        ->name('freelancer.vacation.delete');


    // vacation and availability
    Route::patch('/update/vacation/{vacation}', [VacationController::class, 'update'])
        ->name('update.vacation');

    Route::patch('/update/availability/{availability}', [AvailabilityController::class, 'update'])
        ->name('update.availability');

    Route::delete('/delete/availability/{availability}', [AvailabilityController::class, 'destroy'])
        ->name('delete.availability');

    Route::delete('/delete/vacation/{vacation}', [VacationController::class, 'destroy'])
        ->name('delete.vacation');

    // delete.availability.series
    Route::delete('/delete/availability/series/{availabilitySeries}', [AvailabilityController::class, 'destroySeries'])
        ->name('delete.availability.series');

    // delete.vacation.series
    Route::delete('/delete/vacation/series/{vacationSeries}', [VacationController::class, 'destroySeries'])
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
    )->name('service_provider.update.workProfile')->can('can manage workers');
    Route::patch(
        '/service-provider/{serviceProvider}/terms',
        [ServiceProviderController::class, 'updateTerms']
    )->name('service_provider.update.terms');
    Route::patch(
        '/service-provider/{serviceProvider}/updateCraftSettings',
        [ServiceProviderController::class, 'updateCraftSettings']
    )->name('service_provider.update.craftSettings');
    Route::patch(
        '/service-provider/{serviceProvider}/shift-qualification',
        [ServiceProviderController::class, 'updateShiftQualification']
    )->name('service_provider.update.shift-qualification');
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

    Route::post('/user/vacation/{user}/add', [VacationController::class, 'store'])->name('user.vacation.add');
    Route::patch('/user/vacation/{userVacations}/update', [VacationController::class, 'update'])
        ->name('user.vacation.update');
    Route::delete('/user/vacation/{userVacations}/delete', [VacationController::class, 'destroy'])
        ->name('user.vacation.delete');


    Route::group(['prefix' => 'settings'], function (): void {
        Route::get('shift', [ShiftSettingsController::class, 'index'])->name('shift.settings');
        Route::patch(
            'shift-settings/updateShiftSettingsUseFirstNameForSort',
            [
                ShiftSettingsController::class,
                'updateShiftSettingsUseFirstNameForSort'
            ]
        )->name('shift.settings.update.shift-settings.use-first-name-for-sort');
        Route::post('shift/add/craft', [CraftController::class, 'store'])->name('craft.store');
        Route::patch('shift/update/craft/{craft}', [CraftController::class, 'update'])->name('craft.update');
        Route::delete('shift/delete/craft/{craft}', [CraftController::class, 'destroy'])->name('craft.delete');
        Route::post('/crafts/reorder', [CraftController::class, 'reorder'])->name('craft.reorder');
        Route::patch(
            'shift/update/relevant/event-type/{eventType}',
            [EventTypeController::class, 'updateRelevant']
        )->name('event-type.update.relevant');
        Route::patch(
            'inventory/update/relevant/event-type/{eventType}',
            [EventTypeController::class, 'updateRelevantForInventory']
        )->name('event-type.update.inventory.relevant');
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
    Route::delete('/preset/timeline/{presetTimeLine}/delete', [PresetTimeLineController::class, 'destroy'])
        ->name('preset.delete.timeline.row');
    Route::post('/preset/{shiftPreset}/add', [PresetTimeLineController::class, 'store'])
        ->name('preset.add.timeline.row');

    Route::post(
        '/shift/{event}/{shiftPreset}/import/preset/',
        [ShiftPresetController::class, 'import']
    )
        ->name('shift.preset.import');

    Route::patch('/preset/timeline/update', [PresetTimeLineController::class, 'update'])
        ->name('preset.timeline.update');
    Route::delete(
        '/preset/timeline/{presetTimeLine}/delete',
        [PresetTimeLineController::class, 'destroy']
    )
        ->name('preset.delete.timeline.row');
    Route::post('/preset/{shiftPreset}/add', [PresetTimeLineController::class, 'store'])
        ->name('preset.add.timeline.row');

    Route::patch('/user/{user}/check/vacation', [VacationController::class, 'checkVacation'])
        ->name('user.check.vacation');

    Route::post('/individual-times/add', [IndividualTimeController::class, 'store'])
        ->name('add.update.individualTimesAndShiftPlanComment');

    Route::delete('/individual-times/{individualTime}/delete', [IndividualTimeController::class, 'destroy'])
        ->name('delete.individualTimes');

    Route::patch('/freelancer/{freelancer}/check/vacation', [VacationController::class, 'checkVacationFreelancer'])
        ->name('freelancer.check.vacation');

    Route::post('/calendar/export/pdf', [ExportPDFController::class, 'createPDF'])->name('calendar.export.pdf');
    Route::get(
        '/calendar/export/pdf/{filename}/download',
        [ExportPDFController::class, 'download']
    )->name('calendar.export.pdf.download');

    Route::post('/shift/multiedit/save', [ShiftController::class, 'saveMultiEdit'])->name('shift.multi.edit.save');

    Route::resource('permission-presets', PermissionPresetController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->middleware('role:artwork admin');

    Route::resource('shift-qualifications', ShiftQualificationController::class)->only(
        [
            'store',
            'update',
            'destroy'
        ]
    );

    Route::group(['prefix' => 'day-service'], function (): void {
        Route::get('index', [DayServiceController::class, 'index'])->name('day-service.index');
        Route::post('store', [DayServiceController::class, 'store'])->name('day-service.store');
        Route::patch('update/{dayService}', [DayServiceController::class, 'update'])
            ->name('day-service.update');
    });

    Route::group(['prefix' => 'settings'], function (): void {
        Route::group(['prefix' => 'tab'], function (): void {
            Route::get('index', [ProjectTabController::class, 'index'])
                ->name('tab.index')
                ->middleware('role:artwork admin');
            Route::post('/{projectTab}/update/component/order', [ProjectTabController::class,
                'updateComponentOrder'])
                ->name('tab.update.component.order');
            //tab.add.component
            Route::post('/{projectTab}/add/component', [ProjectTabController::class,
                'addComponent'])->name('tab.add.component');
            // tab.remove.component
            Route::delete('/{projectTab}/remove/component', [
                ProjectTabController::class,
                'removeComponent'
            ])->name('tab.remove.component');

            // post tab.update.component.note
            Route::patch('/{componentInTab}/update/component/note', [ProjectTabController::class, 'updateComponentNote'])
                ->name('tab.update.component.note');

            // post project-management-builder.add.disclosure.component
            Route::post('/add/disclosure/component', [ProjectTabController::class, 'addDisclosureComponent'])
                ->name('project-management-builder.add.disclosure.component');

            // tab.destroy
            Route::delete('/{projectTab}/destroy', [ProjectTabController::class, 'destroy'])
                ->name('tab.destroy');
            // tab.update
            Route::patch('/{projectTab}/update', [ProjectTabController::class, 'update'])
                ->name('tab.update');

            //delete tab.remove.component.in.disclosure
            Route::delete('/remove/component/disclosure', [ProjectTabController::class, 'removeComponentFormDisclosure'])
                ->name('tab.remove.component.in.disclosure');

            // tab.store
            Route::post('/store', [ProjectTabController::class, 'store'])->name('tab.store');
            //tab.reorder
            Route::post('/reorder', [ProjectTabController::class, 'reorder'])
                ->name('tab.reorder');
            //tab.add.component.sidebar
            Route::post(
                '/{projectTabSidebarTab}/add/component/sidebar',
                [ProjectTabController::class,
                    'addComponentSidebar']
            )
                ->name('tab.add.component.sidebar');
            // tab.add.component.with.scopes
            Route::post('/{projectTab}/add/component/with/scopes', [
                ProjectTabController::class,
                'addComponentWithScopes'
            ])->name('tab.add.component.with.scopes');

            // patch tab.update.default
            Route::patch('/{projectTab}/update/default', [ProjectTabController::class, 'updateDefault'])
                ->name('tab.update.default');
        });
        Route::group(['prefix' => 'component'], function (): void {
            // index
            Route::get('index', [ComponentController::class, 'index'])
                ->name('component.index')
                ->middleware('role:artwork admin');
            // project.tab.component.update
            Route::patch('/{project}/{component}/update', [ProjectComponentValueController::class,
                'update'])
                ->name('project.tab.component.update');
            //component.store
            Route::post('/store', [ComponentController::class, 'store'])
                ->name('component.store');
            //component.update
            Route::patch('/{component}/update', [ComponentController::class, 'update'])
                ->name('component.update');
            // component.destroy
            Route::delete('/{component}/destroy', [ComponentController::class, 'destroy'])
                ->name('component.destroy');
        });
        Route::group(['prefix' => 'sidebar'], function (): void {
            Route::delete('/component/{sidebarTabComponent}/remove', [
                SidebarTabComponentController::class,
                'removeComponent'
            ])
                ->name('sidebar.component.remove');
            //tab.sidebar.update
            Route::patch('/{projectTabSidebarTab}/update', [ProjectTabSidebarTabController::class,
                'update'])
                ->name('tab.sidebar.update');

            //tab.sidebar.store
            Route::post('/{projectTab}/store', [ProjectTabSidebarTabController::class, 'store'])
                ->name('tab.sidebar.store');
            //sidebar.tab.update.component.order
            Route::post('/{projectTabSidebarTab}/update/component/order', [
                ProjectTabSidebarTabController::class,
                'updateComponentOrder'
            ])
                ->name('sidebar.tab.update.component.order');
            // tab.sidebar.update
            Route::patch('/{projectTabSidebarTab}/update', [ProjectTabSidebarTabController::class,
                'update'])
                ->name('tab.sidebar.update');
            //sidebar.tab.reorder
            Route::post('/reorder', [ProjectTabSidebarTabController::class,
                'reorder'])
                ->name('sidebar.tab.reorder');
        });

        Route::group(['prefix' => 'calendar'], function () {
            Route::get('/', [CalendarController::class, 'settingIndex'])->name('calendar.settings');

            // post: calendar-settings.store
            Route::post('/store', [CalendarController::class, 'storeSettings'])->name('calendar-settings.store');
        });
    });

    Route::group(['prefix' => 'user'], function (): void {
        Route::patch('/{user}/update/checklist/filter', [UserController::class, 'updateChecklistFilter'])
            ->name('user.update.checklist.filter');
        Route::get('/{user}/own/operation/plan', [UserController::class, 'operationPlan'])
            ->name('user.operationPlan');
        Route::post('/{user}/toggle/compactMode', [UserController::class, 'compactMode'])
            ->name('user.compact.mode.toggle');
        // user.update.show_crafts
        Route::patch('/{user}/update/show/crafts', [UserController::class, 'updateShowCrafts'])
            ->name('user.update.show_crafts');
        Route::patch(
            '/{user}/update/show/shift-qualifications',
            [
                UserController::class,
                'updateShowShiftQualifications'
            ]
        )->name('user.update.show_shift-qualifications');
        Route::patch(
            '/{user}/update/shift-plan-user-sort-by',
            [
                UserController::class, 'updateShiftPlanUserSortBy'
            ]
        )->name('user.update.shiftPlanUserSortBy');

        Route::patch(
            '/{user}/update/shift-tab-user-sort-by',
            [
                UserController::class, 'updateShiftTabUserSortBy'
            ]
        )->name('user.update.shift_tab_sort');


        Route::patch('/user/{user}/inventory/sort', [UserController::class, 'updateInventorySortColumn'])
            ->name('user.update.inventory.sort');

        //user.calendar.go.to.stepper
        Route::patch('/{user}/calendar/go/to/stepper', [UserController::class, 'calendarGoToStepper'])
            ->name('user.calendar.go.to.stepper');

        Route::patch('/{user}/update/userOverviewHeight', [UserController::class, 'updateUserOverviewHeight'])
            ->name('user.update.userOverviewHeight');

        // save user shift calendar abo
        Route::post(
            '/shift/calendar/abo/create',
            [UserShiftCalendarAboController::class, 'store']
        )->name('user.shift.calendar.abo.create');

        // user.shift.calendar.abo.update
        Route::patch(
            '/shift/calendar/abo/{userShiftCalendarAbo}/update',
            [UserShiftCalendarAboController::class, 'update']
        )->name('user.shift.calendar.abo.update');

        // save user calendar abo
        Route::post(
            '/calendar/abo/create',
            [UserCalenderAboController::class, 'store']
        )->name('user.calendar.abo.create');

        // user.shift.calendar.abo.update
        Route::patch(
            '/calendar/abo/{userCalenderAbo}/update',
            [UserCalenderAboController::class, 'update']
        )->name('user.calendar.abo.update');
    });

    Route::resource('project-roles', ProjectRoleController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->middleware('role:artwork admin');

    // route for shift time preset
    Route::group(['prefix' => 'shift-time-preset'], function (): void {
        Route::resource('shift-time-preset', ShiftTimePresetController::class)
            ->only(['store', 'update', 'destroy']);
    });

    // attach DayService to entity
    Route::post(
        '/day-service/{dayService}/attach/{dayServiceable}',
        [DayServiceController::class, 'attachDayServiceable']
    )
        ->name('day-service.attach');


    Route::group(['prefix' => 'inventory'], function (): void {
        Route::get('/', [\Artwork\Modules\Inventory\Http\Controllers\InventoryCategoryController::class, 'index'])
            ->name('inventory.index');

        Route::get('/category/{inventoryCategory}', [\Artwork\Modules\Inventory\Http\Controllers\InventoryCategoryController::class, 'index'])
            ->name('inventory.category.show');

        Route::get('/category/{inventoryCategory}/sub/{inventorySubCategory}', [\Artwork\Modules\Inventory\Http\Controllers\InventoryCategoryController::class, 'index'])
            ->name('inventory.sub.category.show');

        // post inventory-management.articles.store
        Route::post('/articles/store', [InventoryArticleController::class, 'store'])
            ->name('inventory-management.articles.store');

        // patch inventory-management.articles.update
        Route::patch('/articles/{inventoryArticle}/update', [InventoryArticleController::class, 'update'])
            ->name('inventory-management.articles.update');

        // delete articles.destroy
        Route::delete('/articles/{inventoryArticle}/destroy', [InventoryArticleController::class, 'destroy'])
            ->name('articles.destroy');

        // get inventory.articles.trash
        Route::get('/articles/trash', [InventoryArticleController::class, 'indexTrash'])
            ->name('inventory.articles.trash');

        // delete articles.forceDelete
        Route::delete('/articles/{inventoryArticle}/forceDelete', [InventoryArticleController::class, 'forceDelete'])
            ->name('articles.forceDelete');

        // patch articles.restore
        Route::patch('/articles/{inventoryArticle}/restore', [InventoryArticleController::class, 'restore'])
            ->name('articles.restore');
    });




    Route::resource('manufacturers', ManufacturerController::class)->only(
        [
            'index',
            'store',
            'update',
            'destroy'
        ]
    );

    Route::group(['prefix' => 'planning-event-calendar'], function (): void {
        Route::get('/', [EventController::class, 'viewPlanningCalendar'])->name('planning-event-calendar.index');
    });


    //remove.day.service.from.user
    Route::patch(
        '/day-service/remove/{dayServiceable}',
        [DayServiceController::class, 'removeDayServiceable']
    )
        ->name('remove.day.service.from.user');

    Route::group(['prefix' => 'inventory-management'], function (): void {
        Route::get('/', [InventoryController::class, 'inventory'])
            ->name('inventory-management.inventory');

        Route::group(['prefix' => 'settings'], function (): void {
            Route::get('/index', [InventorySettingsController::class, 'index'])
                ->name('inventory-management.settings.index');

            Route::get('/categories', [InventoryCategoryController::class, 'settings'])
                ->name('inventory-management.settings.category');

            Route::get('/properties', [InventoryArticlePropertiesController::class, 'index'])
                ->name('inventory-management.settings.properties');

            // post create Article Property
            Route::post('/properties/create', [InventoryArticlePropertiesController::class, 'store'])
                ->name('inventory-management.settings.properties.create');

            // delete Article Property
            Route::delete('/properties/{inventoryArticleProperty}', [InventoryArticlePropertiesController::class, 'destroy'])
                ->name('inventory-management.settings.properties.delete');

            // patch inventory-management.settings.properties.update
            Route::patch('/properties/{inventoryArticleProperty}/update', [InventoryArticlePropertiesController::class, 'update'])
                ->name('inventory-management.settings.properties.update');

            // inventory-management.settings.categories.create
            Route::post('/categories/create', [InventoryCategoryController::class, 'store'])
                ->name('inventory-management.settings.categories.create');

            // update inventory-management.settings.categories.update
            Route::patch('/categories/{inventoryCategory}/update', [InventoryCategoryController::class, 'update'])
                ->name('inventory-management.settings.categories.update');

            // delete inventory-management.settings.categories.subcategories.delete
            Route::delete('/sub-categories/{inventorySubCategory}/destroy', [InventorySubCategoryController::class, 'destroy'])
                ->name('inventory-management.settings.categories.subcategories.delete');

            // delete categories.destroy
            Route::delete('/categories/{inventoryCategory}/destroy', [InventoryCategoryController::class, 'destroy'])
                ->name('inventory-management.settings.categories.delete');
        });

        Route::group(['prefix' => 'inventory'], function (): void {
            Route::group(['prefix' => 'column'], function (): void {
                Route::post(
                    '/create',
                    [CraftsInventoryColumnController::class, 'create']
                )->name('inventory-management.inventory.column.create');
                Route::post(
                    '/duplicate',
                    [CraftsInventoryColumnController::class, 'duplicate']
                )->name('inventory-management.inventory.column.duplicate');
                Route::patch(
                    '/{craftsInventoryColumn}/name',
                    [CraftsInventoryColumnController::class, 'updateName']
                )->name('inventory-management.inventory.column.update.name');
                Route::patch(
                    '/{craftsInventoryColumn}/select-options',
                    [CraftsInventoryColumnController::class, 'updateTypeOptions']
                )->name('inventory-management.inventory.column.update.type_options');
                Route::patch(
                    '/{craftsInventoryColumn}/background_color',
                    [CraftsInventoryColumnController::class, 'updateBackgroundColor']
                )->name('inventory-management.inventory.column.update.background_color');
                Route::delete(
                    '/{craftsInventoryColumn}',
                    [CraftsInventoryColumnController::class, 'forceDelete']
                )->name('inventory-management.inventory.column.delete');
            });
            Route::group(['prefix' => 'columns'], function (): void {
                Route::patch(
                    '/reorder',
                    [CraftsInventoryColumnController::class, 'reorderColumns']
                )->name('inventory-management.inventory.columns.reorder');
            });
            Route::group(['prefix' => 'category'], function (): void {
                Route::post(
                    '/create',
                    [CraftInventoryCategoryController::class, 'create']
                )->name('inventory-management.inventory.category.create');
                Route::patch(
                    '/{craftInventoryCategory}/name',
                    [CraftInventoryCategoryController::class, 'updateName']
                )->name('inventory-management.inventory.category.update.name');
                Route::patch(
                    '/{craftInventoryCategory}/order',
                    [CraftInventoryCategoryController::class, 'updateOrder']
                )->name('inventory-management.inventory.category.update.order');
                Route::delete(
                    '/{craftInventoryCategory}',
                    [CraftInventoryCategoryController::class, 'forceDelete']
                )->name('inventory-management.inventory.category.delete');
            });
            Route::group(['prefix' => 'group'], function (): void {
                Route::post(
                    '/create',
                    [CraftInventoryGroupController::class, 'create']
                )->name('inventory-management.inventory.group.create');
                Route::patch(
                    '/{craftInventoryGroup}/name',
                    [CraftInventoryGroupController::class, 'updateName']
                )->name('inventory-management.inventory.group.update.name');
                Route::patch(
                    '/{craftInventoryGroup}/order',
                    [CraftInventoryGroupController::class, 'updateOrder']
                )->name('inventory-management.inventory.group.update.order');
                Route::delete(
                    '/{craftInventoryGroup}',
                    [CraftInventoryGroupController::class, 'forceDelete']
                )->name('inventory-management.inventory.group.delete');
            });
            Route::group(['prefix' => 'folder'], function (): void {
                Route::post(
                    '/create',
                    [CraftInventoryGroupFolderController::class, 'create']
                )->name('inventory-management.inventory.folder.create');
                Route::delete(
                    '/{craftInventoryGroupFolder}',
                    [CraftInventoryGroupFolderController::class, 'destroy']
                )->name('inventory-management.inventory.folder.delete');
                // update name of folder
                Route::patch(
                    '/{craftInventoryGroupFolder}/name',
                    [CraftInventoryGroupFolderController::class, 'update']
                )->name('inventory-management.inventory.folder.update.name');
                // PATCH inventory-management.inventory.folder.update.order
                Route::patch(
                    '/inventory/folder/update/order',
                    [CraftInventoryGroupFolderController::class, 'updateOrder']
                )->name('inventory-management.inventory.folder.update.order');
            });
            Route::group(['prefix' => 'item'], function (): void {
                Route::post(
                    '/create',
                    [CraftInventoryItemController::class, 'create']
                )->name('inventory-management.inventory.item.create');
                Route::patch(
                    '/{craftInventoryItem}/add/folder',
                    [CraftInventoryItemController::class, 'addItemToFolder']
                )->name('inventory-management.inventory.item.add.to.folder');
                Route::patch(
                    '/{craftInventoryItem}/add/group',
                    [CraftInventoryItemController::class, 'addItemToGroup']
                )->name('inventory-management.inventory.item.add.to.group');
                Route::patch(
                    '/{craftInventoryItem}/order',
                    [CraftInventoryItemController::class, 'updateOrder']
                )->name('inventory-management.inventory.item.update.order');
                Route::delete(
                    '/{craftInventoryItem}',
                    [CraftInventoryItemController::class, 'forceDelete']
                )->name('inventory-management.inventory.item.delete');
            });
            Route::group(['prefix' => 'cells'], function (): void {
                Route::patch(
                    '/{craftInventoryItemCell}/cell-value',
                    [CraftInventoryItemCellController::class, 'updateCellValue']
                )->name('inventory-management.inventory.item-cell.update.cell-value');
                Route::post(
                    '/{craftInventoryItemCell}/cell-value/upload',
                    [CraftInventoryItemCellController::class, 'updateCellValueUpload']
                )->name('inventory-management.inventory.item-cell.update.cell-value.upload');
                Route::get(
                    '/{craftInventoryItemCell}/cell-value/download',
                    [CraftInventoryItemCellController::class, 'getDownloadCellValueUpload']
                )->name('inventory-management.inventory.item-cell.download');
                Route::delete(
                    '/{craftInventoryItemCell}/cell-value/delete',
                    [CraftInventoryItemCellController::class, 'removeUploadedFile']
                )->name('inventory-management.inventory.item-cell.update.cell-value.delete.file');
            });
            Route::group(['prefix' => 'export'], function (): void {
                Route::post(
                    '/data',
                    [InventoryManagementExportController::class, 'saveExportDataInCache']
                )->name('inventory-management.inventory.export.saveExportDataInCache');
                Route::group(['prefix' => 'create/{cacheToken}'], function (): void {
                    Route::get(
                        '/xlsx',
                        [InventoryManagementExportController::class, 'downloadXlsx']
                    )->name('inventory-management.inventory.export.download-xlsx');
                    Route::get(
                        '/pdf',
                        [InventoryManagementExportController::class, 'downloadPdf']
                    )->name('inventory-management.inventory.export.download-pdf');
                });
            });
            Route::patch('/filter', [CraftInventoryFilterController::class, 'updateOrCreate'])
                ->name('inventory-management.inventory.filter.update');
        });

        Route::get('/scheduling', [InventoryController::class, 'scheduling'])
            ->name('inventory-management.scheduling');

        // inventory.dropItemToEvent
        Route::post('/inventory/dropItemToEvent/{item}/{event}', [InventoryController::class, 'dropItemToEvent'])
            ->name('inventory.dropItemToEvent');

        // inventory.events.destroy
        Route::delete(
            '/inventory/events/{craftInventoryItemEvent}',
            [CraftInventoryItemEventController::class, 'destroy']
        )
            ->name('inventory.events.destroy');

        // patch inventory.updateEvent
        Route::patch(
            '/inventory/updateEvent/{craftInventoryItemEvent}',
            [CraftInventoryItemEventController::class, 'update']
        )
            ->name('inventory.updateEvent');

        // post inventory.multi.events.store
        Route::post(
            '/inventory/multi/events/store',
            [CraftInventoryItemEventController::class, 'storeMultiple']
        )
            ->name('inventory.multi.events.store');
    });

    Route::group(['prefix' => 'searching'], function (): void {
        Route::post('/search/users', [UserController::class, 'scoutSearch'])->name('user.scoutSearch');
        Route::post('/search/workers', [WorkerController::class, 'scoutWorkerSearch'])->name('worker.scoutSearch');
        Route::post('/search/projects', [ProjectController::class, 'scoutSearch'])->name('project.scoutSearch');
    });

    Route::group(['prefix' => 'chat'], function (): void {
        // get chat-system.get-chats
        Route::get('/get-chats', [ChatController::class, 'getChats'])->name('chat-system.get-chats');

        // chat-system.get-chat-messages
        Route::get('/get-chat-messages/{chat}', [ChatController::class, 'getChatMessages'])
            ->name('chat-system.get-chat-messages');

        // post chat-system.send-message
        Route::post('/send-message/{chat}', [ChatController::class, 'sendMessage'])->name('chat-system.send-message');
    });

    Route::resource('holidays', HolidayController::class)
        ->only(['index', 'store', 'update', 'destroy', 'show']);

    Route::group(['prefix' => 'export'], function (): void {
        Route::post(
            '/event-list-or-calendar',
            [EventListOrCalendarExportController::class, 'cacheExportConfiguration']
        )->name('export.cache-filter');
        Route::get(
            '/event-list-xlsx/{cacheToken}',
            [EventListOrCalendarExportController::class, 'downloadEventListXlsx']
        )->name('export.download-event-list-xlsx');
        Route::get(
            '/calendar-xlsx/{cacheToken}',
            [EventListOrCalendarExportController::class, 'downloadCalendarXlsx']
        )->name('export.download-calendar-xlsx');
    });

    Route::group(['prefix' => 'project-management-builder'], function (): void {
        Route::get('/', [ProjectManagementBuilderController::class, 'index'])
            ->name('project-management-builder.index');

        // patch: project-management-builder.update.order
        Route::patch('/update/order', [ProjectManagementBuilderController::class, 'updateOrder'])
            ->name('project-management-builder.update.order');

        // post project-management-builder.store
        Route::post('/store/{component}', [ProjectManagementBuilderController::class, 'store'])
            ->name('project-management-builder.store');

        // delete project-management-builder.destroy
        Route::delete('/destroy/{component}', [ProjectManagementBuilderController::class, 'destroy'])
            ->name('project-management-builder.destroy');
    });

    Route::group(['prefix' => 'project-print-layout'], function(): void {
        Route::get('/', [ProjectPrintLayoutController::class, 'index'])
            ->name('project-print-layout.index');

        Route::post('/store', [ProjectPrintLayoutController::class, 'store'])
            ->name('project-print-layout.store');

        // project.print.layout.add.component
        Route::post('/{projectPrintLayout}/add/component', [ProjectPrintLayoutController::class, 'addComponent'])
            ->name('project.print.layout.add.component');

        // project-print-layout.components.destroy
        Route::delete('/components/{printLayoutComponent}', [ProjectPrintLayoutController::class, 'destroyComponent'])
            ->name('project-print-layout.components.destroy');

        // project-print-layout.update
        Route::patch('/{projectPrintLayout}/update', [ProjectPrintLayoutController::class, 'update'])
            ->name('project-print-layout.update');

        // project-print-layout.update.header.note
        Route::patch('/{projectPrintLayout}/update/header/note', [ProjectPrintLayoutController::class, 'updateHeaderNote'])
            ->name('project-print-layout.update.header.note');

        // project.print.layout
        Route::get('/print/{project}/{projectPrintLayout}', [ProjectPrintLayoutController::class, 'show'])
            ->name('project-print-layout.show');

        // project-print-layout.destroy
        Route::delete('/destroy/{projectPrintLayout}', [ProjectPrintLayoutController::class, 'destroy'])
            ->name('project-print-layout.destroy');
    });


    Route::group(['prefix' => 'event-verifications'], function(){
        // POST events.sendToVerification
        Route::post('/event/{event}/sendToVerification', [EventVerificationController::class, 'store'])
            ->name('events.sendToVerification');

        // GET event-verifications.index
        Route::get('/', [EventVerificationController::class, 'index'])
            ->name('event-verifications.index');

        Route::post('/verification-request/{eventVerification}/approved', [EventVerificationController::class, 'approved'])
            ->name('event-verifications.approved');

        // event-verifications.rejected
        Route::post('/verification-request/{eventVerification}/rejected', [EventVerificationController::class, 'rejected'])
            ->name('event-verifications.rejected');

        // event-verifications.cancel-verification
        Route::post('/verification-request/{event}/cancel-verification', [EventVerificationController::class, 'cancelVerification'])
            ->name('event-verifications.cancel-verification');

        // event-verifications.approved-by-event
        Route::post('/verification-request/{event}/approved-by-event', [EventVerificationController::class, 'approvedByEvent'])
            ->name('event-verifications.approved-by-event');

        // event-verifications.reject-by-event
        Route::post('/verification-request/{event}/reject-by-event', [EventVerificationController::class, 'rejectByEvent'])
            ->name('event-verifications.reject-by-event');

        // event-verifications.reject-by-events
        Route::post('/verification-request/reject-by-events', [EventVerificationController::class, 'rejectByEvents'])
            ->name('event-verifications.reject-by-events');

        // event-verifications.approved-by-events
        Route::post('/verification-request/approved-by-events', [EventVerificationController::class, 'approvedByEvents'])
            ->name('event-verifications.approved-by-events');

        // event-verifications.request-verification
        Route::post('/verification-request/request-verification', [EventVerificationController::class, 'requestVerification'])
            ->name('events-verifications.request-verification');
    });
});

Route::get(
    '/shift/calendar/abo/{calendar_abo_id}',
    [UserShiftCalendarAboController::class, 'show']
)->name('user-shift-calendar-abo.show');

Route::get(
    '/calendar/abo/{calendar_abo_id}',
    [UserCalenderAboController::class, 'show']
)->name('user-calendar-abo.show');
