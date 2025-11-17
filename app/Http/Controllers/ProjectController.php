<?php

namespace App\Http\Controllers;

use App\Settings\EventSettings;
use Artwork\Core\Http\Requests\SearchRequest;
use Artwork\Modules\Accommodation\Models\Accommodation;
use Artwork\Modules\Area\Services\AreaService;
use Artwork\Modules\ArtistResidency\Enums\TypOfRoom;
use Artwork\Modules\ArtistResidency\Models\Artist;
use Artwork\Modules\Budget\Enums\BudgetTypeEnum;
use Artwork\Modules\Budget\Exports\BudgetExport;
use Artwork\Modules\Budget\Models\BudgetSumDetails;
use Artwork\Modules\Budget\Models\CellCalculation;
use Artwork\Modules\Budget\Models\Column;
use Artwork\Modules\Budget\Models\ColumnCell;
use Artwork\Modules\Budget\Models\MainPosition;
use Artwork\Modules\Budget\Models\SubPosition;
use Artwork\Modules\Budget\Models\SubPositionRow;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\Budget\Services\BudgetService;
use Artwork\Modules\Budget\Services\BudgetSumDetailsService;
use Artwork\Modules\Budget\Services\CellCalculationService;
use Artwork\Modules\Budget\Services\CellCommentService;
use Artwork\Modules\Budget\Services\ColumnCellService;
use Artwork\Modules\Budget\Services\ColumnService;
use Artwork\Modules\Budget\Services\MainPositionDetailsService;
use Artwork\Modules\Budget\Services\MainPositionService;
use Artwork\Modules\Budget\Services\MainPositionVerifiedService;
use Artwork\Modules\Budget\Services\RowCommentService;
use Artwork\Modules\Budget\Services\SageAssignedDataCommentService;
use Artwork\Modules\Budget\Services\SageAssignedDataService;
use Artwork\Modules\Budget\Services\SageNotAssignedDataService;
use Artwork\Modules\Budget\Services\SubPositionRowService;
use Artwork\Modules\Budget\Services\SubPositionService;
use Artwork\Modules\Budget\Services\SubPositionSumDetailService;
use Artwork\Modules\Budget\Services\SubPositionVerifiedService;
use Artwork\Modules\Budget\Services\SumCommentService;
use Artwork\Modules\Budget\Services\SumMoneySourceService;
use Artwork\Modules\Budget\Services\TableService;
use Artwork\Modules\Budget\Services\BudgetColumnSettingService;
use Artwork\Modules\Calendar\DTO\ProjectDTO;
use Artwork\Modules\Calendar\Services\CalendarService;
use Artwork\Modules\Category\Models\Category;
use Artwork\Modules\Category\Services\CategoryService;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Checklist\Services\ChecklistService;
use Artwork\Modules\CollectingSociety\Models\CollectingSociety;
use Artwork\Modules\CollectingSociety\Services\CollectingSocietyService;
use Artwork\Modules\CompanyType\Models\CompanyType;
use Artwork\Modules\CompanyType\Services\CompanyTypeService;
use Artwork\Modules\Contract\Models\ContractType;
use Artwork\Modules\Contract\Services\ContractTypeService;
use Artwork\Modules\CostCenter\Models\CostCenter;
use Artwork\Modules\CostCenter\Services\CostCenterService;
use Artwork\Modules\Craft\Services\CraftService;
use Artwork\Modules\Currency\Models\Currency;
use Artwork\Modules\Currency\Services\CurrencyService;
use Artwork\Modules\InternalIssue\Models\InternalIssue;
use Artwork\Modules\Notification\Services\DatabaseNotificationService;
use Artwork\Modules\Department\Models\Department;
use Artwork\Modules\Event\Http\Resources\MinimalCalendarEventResource;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Models\EventStatus;
use Artwork\Modules\Event\Services\EventService;
use Artwork\Modules\Event\Services\EventCommentService;
use Artwork\Modules\Event\Services\EventPropertyService;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\EventType\Services\EventTypeService;
use Artwork\Modules\Filter\Services\FilterService;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Freelancer\Services\FreelancerService;
use Artwork\Modules\Genre\Models\Genre;
use Artwork\Modules\Genre\Services\GenreService;
use Artwork\Modules\MoneySource\Models\MoneySource;
use Artwork\Modules\MoneySource\Services\MoneySourceCalculationService;
use Artwork\Modules\MoneySource\Services\MoneySourceThresholdReminderService;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\DTOs\ProjectSearchDTO;
use Artwork\Modules\Project\Enum\ProjectSortEnum;
use Artwork\Modules\Project\Exports\BudgetsByBudgetDeadlineExport;
use Artwork\Modules\Project\Exports\DetailedBudgetsByBudgetDeadlineExport;
use Artwork\Modules\Project\Http\Requests\ProjectCreateSettingsUpdateRequest;
use Artwork\Modules\Project\Http\Requests\ProjectIndexPaginateRequest;
use Artwork\Modules\Project\Http\Requests\StoreProjectRequest;
use Artwork\Modules\Project\Http\Requests\UpdateProjectRequest;
use Artwork\Modules\Project\Http\Resources\ProjectEditResource;
use Artwork\Modules\Project\Http\Resources\ProjectIndexResource;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectCreateSettings;
use Artwork\Modules\Project\Models\ProjectState;
use Artwork\Modules\Project\Services\CommentService;
use Artwork\Modules\Project\Services\ProjectFileService;
use Artwork\Modules\Project\Services\ProjectService;
use Artwork\Modules\Project\Services\ProjectSettingsService;
use Artwork\Modules\Project\Services\ProjectStateService;
use Artwork\Modules\Project\Models\ProjectManagementBuilder;
use Artwork\Modules\Project\Services\ProjectManagementBuilderService;
use Artwork\Modules\Project\Services\ProjectPrintLayoutService;
use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\ProjectTab;
use Artwork\Modules\Project\Services\ProjectTabService;
use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Room\Services\RoomService;
use Artwork\Modules\Sage100\Services\Sage100Service;
use Artwork\Modules\SageApiSettings\Services\SageApiSettingsService;
use Artwork\Modules\Scheduling\Services\SchedulingService;
use Artwork\Modules\Sector\Models\Sector;
use Artwork\Modules\Sector\Services\SectorService;
use Artwork\Modules\ServiceProvider\Enums\ServiceProviderTypes;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\ServiceProvider\Services\ServiceProviderService;
use Artwork\Modules\Shift\Enums\ShiftTabSort;
use Artwork\Modules\Shift\Services\ShiftFreelancerService;
use Artwork\Modules\Shift\Services\ShiftService;
use Artwork\Modules\Shift\Services\ShiftServiceProviderService;
use Artwork\Modules\Shift\Services\ShiftsQualificationsService;
use Artwork\Modules\Shift\Services\ShiftUserService;
use Artwork\Modules\Shift\Services\ShiftQualificationService;
use Artwork\Modules\Event\Services\SubEventService;
use Artwork\Modules\Shift\Services\SingleShiftPresetService;
use Artwork\Modules\Task\Services\TaskService;
use Artwork\Modules\Timeline\Http\Requests\UpdateTimelineRequest;
use Artwork\Modules\Timeline\Http\Requests\UpdateTimelinesRequest;
use Artwork\Modules\Timeline\Models\Timeline;
use Artwork\Modules\Timeline\Services\TimelineService;
use Artwork\Modules\User\Enums\UserFilterTypes;
use Artwork\Modules\User\Http\Resources\UserWithoutShiftsResource;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Services\UserService;
use Artwork\Modules\User\Services\UserProjectManagementSettingService;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Inertia\ResponseFactory;
use Intervention\Image\Facades\Image;
use phpDocumentor\Reflection\PseudoTypes\IntegerRange;
use stdClass;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ProjectController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly SchedulingService $schedulingService,
        private readonly ProjectService $projectService,
        private readonly BudgetService $budgetService,
        private readonly BudgetColumnSettingService $budgetColumnSettingService,
        private readonly ChecklistService $checklistService,
        private readonly ProjectTabService $projectTabService,
        private readonly ChangeService $changeService,
        private readonly EventService $eventService,
        private readonly ProjectSettingsService $projectSettingsService,
        private readonly ProjectStateService $projectStateService,
        private readonly CategoryService $categoryService,
        private readonly GenreService $genreService,
        private readonly SectorService $sectorService,
        private readonly CostCenterService $costCenterService,
        private readonly AuthManager $authManager,
        private readonly EventTypeService $eventTypeService,
        private readonly RoomService $roomService,
        private readonly Sage100Service $sage100Service,
        private readonly UserService $userService,
        private readonly UserProjectManagementSettingService $userProjectManagementSettingService,
        private readonly TimelineService $timelineService,
        private readonly ProjectManagementBuilderService $projectManagementBuilderService,
        private readonly UserProjectManagementSettingService $userFilterAndSortSettingService,
        private readonly ProjectPrintLayoutService $projectPrintLayoutService,
        protected readonly SingleShiftPresetService $singleShiftPresetService,
        private readonly FilterService $filterService,
    ) {
    }

    /**
     * @return User[]
     */
    public function projectUserSearch(Request $request): array
    {
        $users = User::search($request->input('query'))->get();
        $project = Project::find($request->input('projectId'));

        $returnUser = [];
        foreach ($users as $user) {
            $projectUser = $project->users()->where('user_id', $user->id)->first();
            if ($projectUser !== null) {
                $returnUser[] = $projectUser;
            }
        }
        return $returnUser;
    }

    public function saveProjectManagementFilter(ProjectIndexPaginateRequest $request): void
    {
        $sortEnum = $request->enum('sort', ProjectSortEnum::class);
        $projectStateIds = $request->collect('project_state_ids')->map(fn(string $id) => (int)$id);
        $projectFilters = $request
            ->collect('project_filters')
            ->mapWithKeys(
                function (string $filter, string $key): array {
                    return [
                        $key => (bool)$filter,
                    ];
                }
            );

        $this->userFilterAndSortSettingService->updateOrCreateIfNecessary(
            $this->userService->getAuthUser(),
            [
                'sort_by' => $sortEnum?->name,
                'project_state_ids' => $projectStateIds->toArray(),
                'project_filters' => $projectFilters->toArray()
            ]
        );
    }

    public function index(ProjectIndexPaginateRequest $request): Response|ResponseFactory
    {

        $user = $this->userService->getAuthUser();
        $userProjectManagementSetting = $this->userProjectManagementSettingService
            ->getFromUser($user)
            ->getAttribute('settings');

        if ($request->integer('entitiesPerPage') && $user->entities_per_page !== $request->integer('entitiesPerPage')) {
            $user->update(['entities_per_page' => $request->integer('entitiesPerPage')]);
        }

        $projects = $this->projectService->paginateProjects(
            $request->string('query'),
            $user->entities_per_page,
            $userProjectManagementSetting['sort_by'] ? ProjectSortEnum::from($userProjectManagementSetting['sort_by']) : null,
            Collection::make($userProjectManagementSetting['project_state_ids']),
            Collection::make($userProjectManagementSetting['project_filters'])
        );

        $components = $this->projectManagementBuilderService
            ->getProjectManagementBuilder(['component']);
        $componentData = Component::whereIn('id', $components->pluck('component_id'))->get()
            ->keyBy('id');

        $pinnedProjects = $this->projectService->pinnedProjects($this->authManager->id());

        $pinnedProjectsComponents = $this->mapProjectsToComponents($pinnedProjects, $components, $componentData);
        $projectComponents = $this->mapProjectsToComponents($projects, $components, $componentData);

        return inertia('Projects/NewProjectManagement', [
            'projects' => $projects,
            'projectComponents' => $projectComponents,
            'components' => $components,
            'pinnedProjects' => $pinnedProjectsComponents,
            'pinnedProjectsAll' => $pinnedProjects,
            'first_project_tab_id' => $this->projectTabService->getDefaultOrFirstProjectTab()->getAttribute('id'),
            'states' => $this->projectStateService->getAll(),
            'projectGroups' => $this->projectService->getProjectGroups(),
            'categories' => $this->categoryService->getAll(),
            'genres' => $this->genreService->getAll(),
            'sectors' => $this->sectorService->getAll(),
            'createSettings' => app(ProjectCreateSettings::class),
            'myLastProject' => $this->projectService->getMyLastProject($this->authManager->id()),
            'eventTypes' => $this->eventTypeService->getAll(),
            'rooms' => $this->roomService->getAllWithoutTrashed(),
            'projectSortEnumNames' => array_column(ProjectSortEnum::cases(), 'name'),
            'userProjectManagementSetting' => $userProjectManagementSetting,
            'eventStatuses' => EventStatus::orderBy('order')->get(),
            'lastProject' => $this->userService->getAuthUser()->lastProject,
            'entitiesPerPage' => $user->entities_per_page
        ]);
    }

    /**
     * Hilfsfunktion zur Vermeidung von Code-Duplikation
     */
    private function mapProjectsToComponents($projects, $components, $componentData)
    {
        return $projects->map(function ($project) use ($components, $componentData) {
            /** @var Project $project */
            $projectData = new stdClass(); // needed for the ProjectShowHeaderComponent
            $projectData->id = $project->id;
            $projectData->firstTabId = $this->projectTabService->getDefaultOrFirstProjectTab();
            $projectData->project_managers = $project->managerUsers;
            $projectData->write_auth = $project->writeUsers;
            $projectData->delete_permission_users = $project->delete_permission_users;

            foreach ($components as $component) {
                $componentFullData = $componentData[$component->component_id] ?? null;

                switch ($component->type) {
                    case ProjectTabComponentEnum::PROJECT_TITLE->value:
                        $projectData->title = $project->name;
                        $projectData->key_visual_path = $project->key_visual_path;
                        $projectData->is_group = $project->is_group;
                        $projectData->color = $project->color;
                        $projectData->icon = $project->icon;
                        break;
                    case ProjectTabComponentEnum::ARTIST_NAME_DISPLAY->value:
                        $projectData->artist_name = $project->artists;
                        break;
                    case ProjectTabComponentEnum::PROJECT_STATUS->value:
                        $projectData->state = ProjectState::find($project->state);
                        break;
                    case ProjectTabComponentEnum::PROJECT_GROUP->value:
                        $projectData->group = $project->groups;
                        $projectData->is_group = $project->is_group;
                        break;
                    case ProjectTabComponentEnum::PROJECT_TEAM->value:
                        $projectData->team = $project->users;
                        break;
                    case ProjectTabComponentEnum::PROJECT_ATTRIBUTES->value:
                        $projectData->attributes = [
                            'Categories' => $project->categories,
                            'Genres' => $project->genres,
                            'Sectors' => $project->sectors
                        ];
                        break;
                    case ProjectTabComponentEnum::RELEVANT_DATES_FOR_SHIFT_PLANNING->value:
                        $projectData->shift_relevant_event_types = $project->shiftRelevantEventTypes;
                        break;
                    case ProjectTabComponentEnum::SHIFT_CONTACT_PERSONS->value:
                        $projectData->shift_contact = $project->shift_contact;
                        break;
                    case ProjectTabComponentEnum::GENERAL_SHIFT_INFORMATION->value:
                        $projectData->shift_description = $project->shift_description;
                        break;
                    case ProjectTabComponentEnum::PROJECT_BUDGET_DEADLINE->value:
                        $projectData->budget_deadline = $project->budget_deadline
                            ? Carbon::parse($project->budget_deadline)->translatedFormat('D, d F Y')
                            : null;
                        break;
                    case ProjectTabComponentEnum::BUDGET_INFORMATIONS->value:
                        $projectData->collecting_society = $project->collectingSociety;
                        $projectData->cost_center = $project->costCenter;
                        $projectData->own_copyright = $project->own_copyright;
                        $projectData->cost_center_description = $project->cost_center_description;
                        break;
                }

                if ($componentFullData && !$componentFullData->special) {
                    $projectData->{$component->type}[$componentFullData->id] =
                        $componentFullData->projectValue()
                            ->where('project_id', $project->id)
                            ->first();
                }
            }

            return $projectData;
        });
    }


    public function updateSettings(
        ProjectCreateSettingsUpdateRequest $request,
        ProjectCreateSettings $settings
    ): RedirectResponse {
        $this->projectSettingsService->store($request, $settings);
        return Redirect::back();
    }

    /**
     * @return array<string, mixed>
     */
    public function searchDepartmentsAndUsers(SearchRequest $request): array
    {
        $query = $request->get('query');

        return [
            //only show departments (teams) if corresponding permission is given, admins are handle by Gate::before
            //in AuthServiceProvider already, can will return true then
            'departments' => Auth::user()->can(PermissionEnum::TEAM_UPDATE->value) ?
                Department::nameLike($query)->get() :
                [],
            'users' => UserWithoutShiftsResource::collection(User::nameOrLastNameLike($query)->get())->resolve()
        ];
    }

    /**
     * @return array<string, mixed>
     * @throws AuthorizationException
     */
    public function search(SearchRequest $request, ProjectService $projectService): Collection
    {
        $this->authorize('viewAny', Project::class);

        $projects = $projectService->scoutSearch($request->get('query'))->get();

        return $projects->map(fn(Project $project) => ProjectSearchDTO::fromModel($project));
        //return ProjectIndexResource::collection($projects)->resolve();
    }

    /**
     * @return Project[]
     */
    public function searchProjectsWithoutGroup(Request $request): array
    {
        $filteredObjects = [];
        $projects = Project::search($request->input('query'))->get();
        foreach ($projects as $project) {
            if ($project->is_group !== 1 || $project->is_group !== true) {
                $filteredObjects[] = $project;
            }
        }
        return $filteredObjects;
    }

    public function create(): Response|ResponseFactory
    {
        return inertia('Projects/Create');
    }


    public function store(
        StoreProjectRequest $request,
    ): JsonResponse|RedirectResponse {
        if (
            !Auth::user()->canAny([
                PermissionEnum::ADD_EDIT_OWN_PROJECT->value,
                PermissionEnum::WRITE_PROJECTS->value,
                PermissionEnum::PROJECT_MANAGEMENT->value
            ])
        ) {
            return response()->json(['error' => 'Not authorized to assign users to a project.'], 403);
        }

        $departments = Department::whereIn('id', collect($request->assigned_departments)->pluck('id'))->get();

        $project = Project::create([
            'name' => $request->name,
            'user_id' => $this->authManager->id(),
            'number_of_participants' => $request->number_of_participants,
            'color' => $request->get('color'),
            'icon' => $request->get('icon'),
            'marked_as_done' => $request->boolean('marked_as_done'),
        ]);

        $is_manager = in_array(Auth::id(), $request->get('assignedUsers'), true);

        $this->projectService->attachUserToProject($project, $this->authManager->id(), $is_manager);

        if (!empty($request->assignedUsers)) {
            $this->projectService->attachManagementUsersWithoutSelf(
                $project,
                $request->collect('assignedUsers'),
                $this->authManager->id()
            );
        }

        $this->projectService->updateProject($project, [
            'name' => $request->string('name'),
            'artists' => $request->string('artists'),
            'budget_deadline' => $request->get('budget_deadline'),
            'state' => $request->integer('state'),
            'marked_as_done' => $request->boolean('marked_as_done'),
            'cost_center_id' => $request->string('cost_center') !== null ?
                $this->costCenterService->findOrCreateCostCenter($request->string('cost_center'))?->id : null
        ]);

        if ($request->boolean('isGroup')) {
            $project->update(['is_group' => true]);
            $project->projectsOfGroup()->sync($request->get('projects'));
        } elseif (!empty($request->selectedGroup)) {
            $group = Project::find($request->selectedGroup['id']);
            $group->projectsOfGroup()->syncWithoutDetaching($project->id);

            // Ensure the group's is_group flag is set to true
            if (!$group->is_group) {
                $group->is_group = true;
                $group->save();
            }
        }

        $this->projectService->syncCategories($project, $request->collect('assignedCategoryIds'));
        $this->projectService->syncSectors($project, $request->collect('assignedSectorIds'));
        $this->projectService->syncGenres($project, $request->collect('assignedGenreIds'));

        $project->departments()->sync($departments->pluck('id'));

        // Generate basic budget values for the project
        $this->budgetService->generateBasicBudgetValues($project);

        // If the project is not a group but is associated with a group,
        // ensure it has at least one column marked as relevant for project groups
        if (!$request->boolean('isGroup') && !empty($request->selectedGroup)) {
            $table = $project->table()->first();
            if ($table) {
                $columns = $table->columns()->get();
                if ($columns->where('relevant_for_project_groups', true)->isEmpty()) {
                    // Mark the last column as relevant for project groups
                    $lastColumn = $columns->sortBy('position')->last();
                    if ($lastColumn) {
                        $lastColumn->update(['relevant_for_project_groups' => true]);
                    }
                }
            }
        }

        $eventRelevantEventTypeIds = EventType::where('relevant_for_shift', true)->pluck('id');
        $project->shiftRelevantEventTypes()->sync($eventRelevantEventTypeIds);

        return Redirect::back();
    }


    public function generateBasicBudgetValues(Project $project): void
    {
        $table = $project->table()->create([
            'name' => $project->name . ' Budgettabelle'
        ]);

        $columns = $table->columns()->createMany([
            [
                'name' => $this->budgetColumnSettingService->getColumnNameByColumnPosition(0),
                'subName' => '',
                'type' => 'empty',
                'position' => 0,
                'linked_first_column' => null,
                'linked_second_column' => null
            ],
            [
                'name' => $this->budgetColumnSettingService->getColumnNameByColumnPosition(1),
                'subName' => '',
                'type' => 'empty',
                'position' => 1,
                'linked_first_column' => null,
                'linked_second_column' => null
            ],
            [
                'name' => $this->budgetColumnSettingService->getColumnNameByColumnPosition(2),
                'subName' => '',
                'type' => 'empty',
                'position' => 2,
                'linked_first_column' => null,
                'linked_second_column' => null
            ],
            [
                'name' => date('Y') . ' €',
                'subName' => 'A',
                'type' => 'empty',
                'position' => 3,
                'linked_first_column' => null,
                'linked_second_column' => null
            ],
        ]);

        $costMainPosition = $table->mainPositions()->create([
            'type' => BudgetTypeEnum::BUDGET_TYPE_COST,
            'name' => 'Hauptpostion',
            'position' => $table->mainPositions()
                    ->where('type', BudgetTypeEnum::BUDGET_TYPE_COST)->max('position') + 1
        ]);

        $earningMainPosition = $table->mainPositions()->create([
            'type' => BudgetTypeEnum::BUDGET_TYPE_EARNING,
            'name' => 'Hauptpostion',
            'position' => $table->mainPositions()
                    ->where('type', BudgetTypeEnum::BUDGET_TYPE_EARNING)->max('position') + 1
        ]);

        $costSubPosition = $costMainPosition->subPositions()->create([
            'name' => 'Unterposition',
            'position' => $costMainPosition->subPositions()->max('position') + 1
        ]);

        $earningSubPosition = $earningMainPosition->subPositions()->create([
            'name' => 'Unterposition',
            'position' => $costSubPosition->subPositionRows()->max('position') + 1
        ]);

        $costSubPositionRow = $costSubPosition->subPositionRows()->create([
            'commented' => false,
            'position' => $costSubPosition->subPositionRows()->max('position') + 1
        ]);

        $earningSubPositionRow = $earningSubPosition->subPositionRows()->create([
            'commented' => false,
            'position' => $earningSubPosition->subPositionRows()->max('position') + 1

        ]);

        $firstThreeColumns = $columns->shift(3);

        foreach ($firstThreeColumns as $firstThreeColumn) {
            $costSubPositionRow->cells()->create(
                [
                    'column_id' => $firstThreeColumn->id,
                    'sub_position_row_id' => $costSubPositionRow->id,
                    'value' => '0',
                    'verified_value' => "",
                    'linked_money_source_id' => null,
                ]
            );
        }

        foreach ($firstThreeColumns as $firstThreeColumn) {
            $earningSubPositionRow->cells()->create(
                [
                    'column_id' => $firstThreeColumn->id,
                    'sub_position_row_id' => $earningSubPositionRow->id,
                    'value' => '0',
                    'verified_value' => "",
                    'linked_money_source_id' => null,
                ]
            );
        }

        foreach ($columns as $column) {
            $costSubPositionRow->cells()->create(
                [
                    'column_id' => $column->id,
                    'sub_position_row_id' => $costSubPositionRow->id,
                    'value' => '0,00',
                    'verified_value' => null,
                    'linked_money_source_id' => null,
                ]
            );
            $earningSubPositionRow->cells()->create(
                [
                    'column_id' => $column->id,
                    'sub_position_row_id' => $earningSubPositionRow->id,
                    'value' => '0,00',
                    'verified_value' => null,
                    'linked_money_source_id' => null,
                ]
            );
        }

        $costMainPosition->mainPositionSumDetails()->create([
            'column_id' => $columns->first()->id
        ]);

        $earningMainPosition->mainPositionSumDetails()->create([
            'column_id' => $columns->first()->id
        ]);

        $costSubPosition->subPositionSumDetails()->create([
            'column_id' => $columns->first()->id
        ]);

        $earningSubPosition->subPositionSumDetails()->create([
            'column_id' => $columns->first()->id
        ]);

        BudgetSumDetails::create([
            'column_id' => $columns->first()->id,
            'type' => 'COST'
        ]);

        BudgetSumDetails::create([
            'column_id' => $columns->first()->id,
            'type' => 'EARNING'
        ]);
    }

    public function verifiedRequestMainPosition(Request $request): RedirectResponse
    {
        $mainPosition = MainPosition::find($request->id);
        $project = $mainPosition->table()->first()->project()->first();

        if ($request->giveBudgetAccess) {
            $project->users()->updateExistingPivot($request->user, ['access_budget' => true]);
            $user = User::find($request->user);
            $notificationTitle = __('notification.project.budget.add', ['project' => $project->name], $user->language);
            $broadcastMessage = [
                'id' => rand(1, 1000000),
                'type' => 'success',
                'message' => $notificationTitle
            ];

            $this->notificationService->setTitle($notificationTitle);
            $this->notificationService->setIcon('green');
            $this->notificationService->setNotificationConstEnum(NotificationEnum::NOTIFICATION_PROJECT);
            $this->notificationService->setBroadcastMessage($broadcastMessage);
            $this->notificationService->setNotificationTo($user);
            $this->notificationService->createNotification();
        }
        $mainPosition->update(['is_verified' => BudgetTypeEnum::BUDGET_VERIFIED_TYPE_REQUESTED]);
        $notificationTitle = __(
            'notification.project.budget.new_verify_request',
            [],
            User::find($request->user)->language
        );
        $budgetData = new stdClass();
        $budgetData->position_id = $mainPosition->id;
        $budgetData->requested_by = Auth::id();
        $budgetData->changeType = BudgetTypeEnum::BUDGET_VERIFICATION_REQUEST;
        $broadcastMessage = [
            'id' => rand(1, 1000000),
            'type' => 'success',
            'message' => $notificationTitle
        ];
        $notificationDescription = [
            1 => [
                'type' => 'string',
                'title' => $mainPosition->name,
                'href' => null
            ],
            2 => [
                'type' => 'link',
                'title' =>  $project ? $project->name : '',
                'href' => $project ?
                    route(
                        'projects.tab',
                        [
                            $project->id,
                            $this->projectTabService->getFirstProjectTabWithTypeIdOrFirstProjectTabId(
                                ProjectTabComponentEnum::BUDGET
                            )
                        ]
                    ) : null,
            ]
        ];
        $this->notificationService->setTitle($notificationTitle);
        $this->notificationService->setIcon('blue');
        $this->notificationService->setNotificationConstEnum(NotificationEnum::NOTIFICATION_BUDGET_STATE_CHANGED);
        $this->notificationService->setPositionVerifyRequestType('main');
        $this->notificationService->setPositionVerifyRequestId($mainPosition->getAttribute('id'));
        $this->notificationService->setBroadcastMessage($broadcastMessage);
        $this->notificationService->setButtons(['calculation_check', 'delete_request']);
        $this->notificationService->setBudgetData($budgetData);
        $this->notificationService->setProjectId($project->id);
        $this->notificationService->setDescription($notificationDescription);
        $this->notificationService->setNotificationTo(User::find($request->user));
        $this->notificationService->createNotification();

        $mainPosition->verified()->create([
            'requested_by' => Auth::id(),
            'requested' => $request->user
        ]);

        $this->changeService->saveFromBuilder(
            $this->changeService
                ->createBuilder()
                ->setType('budget')
                ->setModelClass(Project::class)
                ->setModelId($project->id)
                ->setTranslationKey('Main position requested for verification')
                ->setTranslationKeyPlaceholderValues([$mainPosition->name])
        );

        return Redirect::back();
    }

    public function takeBackVerification(Request $request): RedirectResponse
    {

        $budgetData = new stdClass();
        $budgetData->requested_by = Auth::id();
        $budgetData->changeType = BudgetTypeEnum::BUDGET_VERIFICATION_TAKE_BACK;
        if ($request->type === 'main') {
            $mainPosition = MainPosition::find($request->position['id']);
            $verifiedRequest = $mainPosition->verified()->first();
            $notificationTitle = __(
                'notification.project.budget.delete_verify_request',
                [],
                User::find($verifiedRequest->requested)->language
            );
            $table = $mainPosition->table()->first();
            $project = $table->project()->first();
            // Delete Function Updated to new Notification System
            $this->deleteOldNotification($mainPosition->id, $verifiedRequest->requested);
            $budgetData->position_id = $mainPosition->id;
            $broadcastMessage = [
                'id' => rand(1, 1000000),
                'type' => 'success',
                'message' => $notificationTitle
            ];
            $notificationDescription = [
                1 => [
                    'type' => 'string',
                    'title' => $mainPosition->name,
                    'href' => null
                ],
                2 => [
                    'type' => 'link',
                    'title' =>  $project ? $project->name : '',
                    'href' => $project ? route(
                        'projects.tab',
                        [
                            $project->id,
                            $this->projectTabService->getFirstProjectTabWithTypeIdOrFirstProjectTabId(
                                ProjectTabComponentEnum::BUDGET
                            )
                        ]
                    ) : null,
                ]
            ];
            $this->notificationService->setTitle($notificationTitle);
            $this->notificationService->setIcon('red');
            $this->notificationService
                ->setNotificationConstEnum(NotificationEnum::NOTIFICATION_BUDGET_STATE_CHANGED);
            $this->notificationService->setBroadcastMessage($broadcastMessage);
            $this->notificationService->setBudgetData($budgetData);
            $this->notificationService->setProjectId($project->id);
            $this->notificationService->setDescription($notificationDescription);
            $this->notificationService->setNotificationTo(User::find($verifiedRequest->requested));
            $this->notificationService->createNotification();
            $verifiedRequest->forceDelete();
            $mainPosition->update(['is_verified' => BudgetTypeEnum::BUDGET_VERIFIED_TYPE_NOT_VERIFIED]);

            $this->changeService->saveFromBuilder(
                $this->changeService
                    ->createBuilder()
                    ->setType('budget')
                    ->setModelClass(Project::class)
                    ->setModelId($project->id)
                    ->setTranslationKey('Main position Verification request canceled')
                    ->setTranslationKeyPlaceholderValues([$mainPosition->name])
            );
        }

        if ($request->type === 'sub') {
            $subPosition = SubPosition::find($request->position['id']);
            $mainPosition = $subPosition->mainPosition()->first();
            $verifiedRequest = $subPosition->verified()->first();
            $table = $mainPosition->table()->first();
            $notificationTitle = __(
                'notification.project.budget.delete_verify_request',
                [],
                User::find($verifiedRequest->requested)->language
            );
            $project = $table->project()->first();
            // Delete Function Updated to new Notification System
            $this->deleteOldNotification($subPosition->id, $verifiedRequest->requested);
            $budgetData->position_id = $mainPosition->id;
            $broadcastMessage = [
                'id' => rand(1, 1000000),
                'type' => 'success',
                'message' => $notificationTitle
            ];
            $notificationDescription = [
                1 => [
                    'type' => 'string',
                    'title' => $subPosition->name,
                    'href' => null
                ],
                2 => [
                    'type' => 'link',
                    'title' =>  $project ? $project->name : '',
                    'href' => $project ? route(
                        'projects.tab',
                        [
                            $project->id,
                            $this->projectTabService->getFirstProjectTabWithTypeIdOrFirstProjectTabId(
                                ProjectTabComponentEnum::BUDGET
                            )
                        ]
                    ) : null,
                ]
            ];

            $this->notificationService->setTitle($notificationTitle);
            $this->notificationService->setIcon('red');
            $this->notificationService
                ->setNotificationConstEnum(NotificationEnum::NOTIFICATION_BUDGET_STATE_CHANGED);
            $this->notificationService->setBroadcastMessage($broadcastMessage);
            $this->notificationService->setBudgetData($budgetData);
            $this->notificationService->setProjectId($project->id);
            $this->notificationService->setNotificationTo(User::find($verifiedRequest->requested));
            $this->notificationService->setDescription($notificationDescription);
            $this->notificationService->createNotification();
            $subPosition->update(['is_verified' => BudgetTypeEnum::BUDGET_VERIFIED_TYPE_NOT_VERIFIED]);
            $verifiedRequest->forceDelete();

            $this->changeService->saveFromBuilder(
                $this->changeService
                    ->createBuilder()
                    ->setType('budget')
                    ->setModelClass(Project::class)
                    ->setModelId($project->id)
                    ->setTranslationKey('Sub position Verification request canceled')
                    ->setTranslationKeyPlaceholderValues([$subPosition->name])
            );
        }
        return Redirect::back();
    }

    private function deleteOldNotification($positionId, $requestedId): void
    {
        DatabaseNotification::query()
            ->whereJsonContains("data->budgetData->position_id", $positionId)
            ->whereJsonContains("data->budgetData->requested_by", $requestedId)
            ->whereJsonContains("data->budgetData->changeType", BudgetTypeEnum::BUDGET_VERIFICATION_REQUEST)
            ->delete();
    }

    /**
     * @throws Throwable
     */
    public function removeVerification(
        Request $request,
        DatabaseNotificationService $databaseNotificationService
    ): RedirectResponse {
        $budgetData = new stdClass();
        $budgetData->requested_by = Auth::id();
        $budgetData->changeType = BudgetTypeEnum::BUDGET_VERIFICATION_DELETED;

        if ($request->type === 'main') {
            $mainPosition = MainPosition::find($request->position['id']);
            $verifiedRequest = $mainPosition->verified()->first();
            $notificationTitle = __(
                'notification.project.budget.verify_removed',
                [],
                User::find($verifiedRequest->requested)->language
            );
            $this->removeMainPositionCellVerifiedValue($mainPosition);
            $project = $mainPosition->table()->first()->project()->first();
            $budgetData->position_id = $mainPosition->id;
            $broadcastMessage = [
                'id' => rand(1, 1000000),
                'type' => 'success',
                'message' => $notificationTitle
            ];
            $notificationDescription = [
                1 => [
                    'type' => 'string',
                    'title' => $mainPosition->name,
                    'href' => null
                ],
                2 => [
                    'type' => 'link',
                    'title' =>  $project ? $project->name : '',
                    'href' => $project ? route(
                        'projects.tab',
                        [
                            $project->id,
                            $this->projectTabService->getFirstProjectTabWithTypeIdOrFirstProjectTabId(
                                ProjectTabComponentEnum::BUDGET
                            )
                        ]
                    ) : null,
                ]
            ];
            $this->notificationService->setTitle($notificationTitle);
            $this->notificationService->setIcon('red');
            $this->notificationService->setPriority(2);
            $this->notificationService
                ->setNotificationConstEnum(NotificationEnum::NOTIFICATION_BUDGET_STATE_CHANGED);
            $this->notificationService->setBroadcastMessage($broadcastMessage);
            $this->notificationService->setBudgetData($budgetData);
            $this->notificationService->setProjectId($project->id);
            $this->notificationService->setNotificationTo(User::find($verifiedRequest->requested));
            $this->notificationService->setDescription($notificationDescription);
            $this->notificationService->createNotification();
            $mainPosition->update(['is_verified' => BudgetTypeEnum::BUDGET_VERIFIED_TYPE_NOT_VERIFIED]);
            $verifiedRequest->forceDelete();

            $this->changeService->saveFromBuilder(
                $this->changeService
                    ->createBuilder()
                    ->setType('budget')
                    ->setModelClass(Project::class)
                    ->setModelId($project->id)
                    ->setTranslationKey('Main position Verification canceled')
                    ->setTranslationKeyPlaceholderValues([$mainPosition->name])
            );
        }

        if ($request->type === 'sub') {
            $subPosition = SubPosition::find($request->position['id']);
            $mainPosition = $subPosition->mainPosition()->first();
            $verifiedRequest = $subPosition->verified()->first();
            $notificationTitle = __(
                'notification.project.budget.verify_removed',
                [],
                User::find($verifiedRequest->requested)->language
            );
            $this->removeSubPositionCellVerifiedValue($subPosition);
            $project = $mainPosition->table()->first()->project()->first();
            $budgetData->position_id = $mainPosition->id;
            $broadcastMessage = [
                'id' => rand(1, 1000000),
                'type' => 'success',
                'message' => $notificationTitle
            ];
            $notificationDescription = [
                1 => [
                    'type' => 'string',
                    'title' => $mainPosition->name,
                    'href' => null
                ],
                2 => [
                    'type' => 'link',
                    'title' =>  $project ? $project->name : '',
                    'href' => $project ? route(
                        'projects.tab',
                        [
                            $project->id,
                            $this->projectTabService->getFirstProjectTabWithTypeIdOrFirstProjectTabId(
                                ProjectTabComponentEnum::BUDGET
                            )
                        ]
                    ) : null,
                ]
            ];

            $this->notificationService->setTitle($notificationTitle);
            $this->notificationService->setIcon('red');
            $this->notificationService->setPriority(2);
            $this->notificationService
                ->setNotificationConstEnum(NotificationEnum::NOTIFICATION_BUDGET_STATE_CHANGED);
            $this->notificationService->setBroadcastMessage($broadcastMessage);
            $this->notificationService->setBudgetData($budgetData);
            $this->notificationService->setProjectId($project->id);
            $this->notificationService->setNotificationTo(User::find($verifiedRequest->requested));
            $this->notificationService->setDescription($notificationDescription);
            $this->notificationService->createNotification();
            $subPosition->update(['is_verified' => BudgetTypeEnum::BUDGET_VERIFIED_TYPE_NOT_VERIFIED]);
            $verifiedRequest->forceDelete();

            $this->changeService->saveFromBuilder(
                $this->changeService
                    ->createBuilder()
                    ->setType('budget')
                    ->setModelClass(Project::class)
                    ->setModelId($project->id)
                    ->setTranslationKey('Sub position Verification removed')
                    ->setTranslationKeyPlaceholderValues([$subPosition->name])
            );
        }

        if (($notificationKey = $request->string('notificationKey')->value()) !== '') {
            $databaseNotificationService->deleteByKey($notificationKey);
        }

        return Redirect::back();
    }

    public function verifiedRequestSubPosition(Request $request): RedirectResponse
    {
        $subPosition = SubPosition::find($request->id);
        $mainPosition = $subPosition->mainPosition()->first();
        $project = $mainPosition->table()->first()->project()->first();
        if ($request->giveBudgetAccess) {
            $project->users()->updateExistingPivot($request->user, ['access_budget' => true]);
            $user = User::find($request->user);
            // Notification
            $notificationTitle = __(
                'notification.project.budget.add',
                [],
                $user->language
            );
            $project = $mainPosition->table()->first()->project()->first();
            $broadcastMessage = [
                'id' => rand(1, 1000000),
                'type' => 'success',
                'message' => $notificationTitle
            ];
            $notificationDescription = [
                1 => [
                    'type' => 'string',
                    'title' => $subPosition->name,
                    'href' => null
                ],
                2 => [
                    'type' => 'link',
                    'title' =>  $project ? $project->name : '',
                    'href' => $project ? route(
                        'projects.tab',
                        [
                            $project->id,
                            $this->projectTabService->getFirstProjectTabWithTypeIdOrFirstProjectTabId(
                                ProjectTabComponentEnum::BUDGET
                            )
                        ]
                    ) : null,
                ]
            ];
            $this->notificationService->setTitle($notificationTitle);
            $this->notificationService->setIcon('red');
            $this->notificationService->setPriority(2);
            $this->notificationService
                ->setNotificationConstEnum(NotificationEnum::NOTIFICATION_BUDGET_STATE_CHANGED);
            $this->notificationService->setBroadcastMessage($broadcastMessage);
            $this->notificationService->setNotificationTo($user);
            $this->notificationService->setDescription($notificationDescription);
            $this->notificationService->createNotification();
        }
        $subPosition->update(['is_verified' => BudgetTypeEnum::BUDGET_VERIFIED_TYPE_REQUESTED]);
        $notificationTitle = __(
            'notification.project.budget.new_verify_request',
            [],
            User::find($request->user)->language
        );
        $budgetData = new stdClass();
        $budgetData->position_id = $subPosition->id;
        $budgetData->requested_by = Auth::id();
        $budgetData->changeType = BudgetTypeEnum::BUDGET_VERIFICATION_REQUEST;
        $broadcastMessage = [
            'id' => rand(1, 1000000),
            'type' => 'success',
            'message' => $notificationTitle
        ];
        $notificationDescription = [
            1 => [
                'type' => 'string',
                'title' => $mainPosition->name,
                'href' => null
            ],
            2 => [
                'type' => 'link',
                'title' =>  $project ? $project->name : '',
                'href' => $project ? route(
                    'projects.tab',
                    [
                        $project->id,
                        $this->projectTabService->getFirstProjectTabWithTypeIdOrFirstProjectTabId(
                            ProjectTabComponentEnum::BUDGET
                        )
                    ]
                ) : null,
            ]
        ];
        $this->notificationService->setTitle($notificationTitle);
        $this->notificationService->setIcon('blue');
        $this->notificationService->setPriority(1);
        $this->notificationService->setNotificationConstEnum(NotificationEnum::NOTIFICATION_BUDGET_STATE_CHANGED);
        $this->notificationService->setPositionVerifyRequestType('sub');
        $this->notificationService->setPositionVerifyRequestId($subPosition->getAttribute('id'));
        $this->notificationService->setBroadcastMessage($broadcastMessage);
        $this->notificationService->setNotificationTo(User::find($request->user));
        $this->notificationService->setButtons(['calculation_check', 'delete_request']);
        $this->notificationService->setBudgetData($budgetData);
        $this->notificationService->setProjectId($project->id);
        $this->notificationService->setDescription($notificationDescription);
        $this->notificationService->createNotification();

        $subPosition->verified()->create([
            'requested_by' => Auth::id(),
            'requested' => $request->user
        ]);

        $this->changeService->saveFromBuilder(
            $this->changeService
                ->createBuilder()
                ->setType('budget')
                ->setModelClass(Project::class)
                ->setModelId($project->id)
                ->setTranslationKey('Sub position requested for verification')
                ->setTranslationKeyPlaceholderValues([$subPosition->name])
        );

        return Redirect::back();
    }

    public function verifiedSubPosition(Request $request): RedirectResponse
    {
        $subPosition = SubPosition::find($request->subPositionId);
        $verifiedRequest = $subPosition->verified()->first();
        $this->setSubPositionCellVerifiedValue($subPosition);
        $subPosition->update(['is_verified' => 'BUDGET_VERIFIED_TYPE_CLOSED']);

        DatabaseNotification::query()
            ->whereJsonContains("data->budgetData->position_id", $subPosition->id)
            ->whereJsonContains("data->budgetData->requested_by", $verifiedRequest->requested)
            ->whereJsonContains("data->budgetData->changeType", BudgetTypeEnum::BUDGET_VERIFICATION_REQUEST)
            ->delete();

        $this->changeService->saveFromBuilder(
            $this->changeService
                ->createBuilder()
                ->setType('budget')
                ->setModelClass(Project::class)
                ->setModelId($request->project_id)
                ->setTranslationKey('Sub position verified')
                ->setTranslationKeyPlaceholderValues([$subPosition->name])
        );

        return Redirect::back();
    }

    public function fixSubPosition(Request $request): RedirectResponse
    {
        $subPosition = SubPosition::find($request->subPositionId);
        $this->setSubPositionCellVerifiedValue($subPosition);
        $subPosition->update(['is_fixed' => true]);

        $project = Project::find($request->project_id);
        $budgetData = new stdClass();
        $budgetData->position_id = $subPosition->id;
        $budgetData->requested_by = Auth::id();
        $budgetData->changeType = BudgetTypeEnum::BUDGET_VERIFICATION_REQUEST;

        foreach ($project->access_budget()->get() as $user) {
            $notificationTitle = __(
                'notification.project.budget.fixed',
                [],
                $user->language
            );
            $broadcastMessage = [
                'id' => rand(1, 1000000),
                'type' => 'success',
                'message' => $notificationTitle
            ];
            $notificationDescription = [
                1 => [
                    'type' => 'string',
                    'title' => $subPosition->name,
                    'href' => null
                ],
                2 => [
                    'type' => 'link',
                    'title' =>  $project ? $project->name : '',
                    'href' => $project ? route(
                        'projects.tab',
                        [
                            $project->id,
                            $this->projectTabService->getFirstProjectTabWithTypeIdOrFirstProjectTabId(
                                ProjectTabComponentEnum::BUDGET
                            )
                        ]
                    ) : null,
                ]
            ];
            $this->notificationService->setTitle($notificationTitle);
            $this->notificationService->setIcon('red');
            $this->notificationService->setPriority(2);
            $this->notificationService
                ->setNotificationConstEnum(NotificationEnum::NOTIFICATION_BUDGET_STATE_CHANGED);
            $this->notificationService->setBroadcastMessage($broadcastMessage);
            $this->notificationService->setBudgetData($budgetData);
            $this->notificationService->setDescription($notificationDescription);

            $this->notificationService->setNotificationTo($user);
            $this->notificationService->createNotification();
        }

        $this->changeService->saveFromBuilder(
            $this->changeService
                ->createBuilder()
                ->setType('budget')
                ->setModelClass(Project::class)
                ->setModelId($project->id)
                ->setTranslationKey('Sub position fixed')
                ->setTranslationKeyPlaceholderValues([$subPosition->name])
        );

        return Redirect::back();
    }

    public function unfixSubPosition(Request $request): RedirectResponse
    {
        $subPosition = SubPosition::find($request->subPositionId);
        $this->removeSubPositionCellVerifiedValue($subPosition);
        $subPosition->update(['is_fixed' => false]);
        $project = Project::find($request->project_id);
        $budgetData = new stdClass();
        $budgetData->position_id = $subPosition->id;
        $budgetData->requested_by = Auth::id();
        $budgetData->changeType = BudgetTypeEnum::BUDGET_VERIFICATION_REQUEST;

        foreach ($project->access_budget()->get() as $user) {
            $notificationTitle = __(
                'notification.project.budget.unfixed',
                [],
                $user->language
            );
            $broadcastMessage = [
                'id' => rand(1, 1000000),
                'type' => 'success',
                'message' => $notificationTitle
            ];
            $notificationDescription = [
                1 => [
                    'type' => 'string',
                    'title' => $subPosition->name,
                    'href' => null
                ],
                2 => [
                    'type' => 'link',
                    'title' =>  $project ? $project->name : '',
                    'href' => $project ? route(
                        'projects.tab',
                        [
                            $project->id,
                            $this->projectTabService->getFirstProjectTabWithTypeIdOrFirstProjectTabId(
                                ProjectTabComponentEnum::BUDGET
                            )
                        ]
                    ) : null,
                ]
            ];

            $this->notificationService->setTitle($notificationTitle);
            $this->notificationService->setIcon('red');
            $this->notificationService->setPriority(2);
            $this->notificationService
                ->setNotificationConstEnum(NotificationEnum::NOTIFICATION_BUDGET_STATE_CHANGED);
            $this->notificationService->setBroadcastMessage($broadcastMessage);
            $this->notificationService->setBudgetData($budgetData);
            $this->notificationService->setDescription($notificationDescription);
            $this->notificationService->setNotificationTo($user);
            $this->notificationService->createNotification();
        }

        $this->changeService->saveFromBuilder(
            $this->changeService
                ->createBuilder()
                ->setType('budget')
                ->setModelClass(Project::class)
                ->setModelId($request->project_id)
                ->setTranslationKey('Sub position Fixing canceled')
                ->setTranslationKeyPlaceholderValues([$subPosition->name])
        );

        return Redirect::back();
    }

    public function fixMainPosition(Request $request): RedirectResponse
    {
        $mainPosition = MainPosition::find($request->mainPositionId);
        $this->setMainPositionCellVerifiedValue($mainPosition);
        $mainPosition->update(['is_fixed' => true]);

        $this->changeService->saveFromBuilder(
            $this->changeService
                ->createBuilder()
                ->setType('budget')
                ->setModelClass(Project::class)
                ->setModelId($request->project_id)
                ->setTranslationKey('Main position fixed')
                ->setTranslationKeyPlaceholderValues([$mainPosition->name])
        );

        return Redirect::back();
    }

    public function unfixMainPosition(Request $request): RedirectResponse
    {
        $mainPosition = MainPosition::find($request->mainPositionId);
        $this->removeMainPositionCellVerifiedValue($mainPosition);
        $mainPosition->update(['is_fixed' => false]);

        $this->changeService->saveFromBuilder(
            $this->changeService
                ->createBuilder()
                ->setType('budget')
                ->setModelClass(Project::class)
                ->setModelId($request->project_id)
                ->setTranslationKey('Main position Fixing canceled')
                ->setTranslationKeyPlaceholderValues([$mainPosition->name])
        );

        return Redirect::back();
    }

    public function resetTable(
        Project $project,
        TableService $tableService,
        MainPositionService $mainPositionService,
        ColumnService $columnService,
        SumCommentService $sumCommentService,
        SumMoneySourceService $sumMoneySourceService,
        SubPositionVerifiedService $subPositionVerifiedService,
        SubPositionSumDetailService $subPositionSumDetailService,
        SubPositionRowService $subPositionRowService,
        RowCommentService $rowCommentService,
        ColumnCellService $columnCellService,
        MainPositionVerifiedService $mainPositionVerifiedService,
        MainPositionDetailsService $mainPositionDetailsService,
        SubPositionService $subPositionService,
        BudgetSumDetailsService $budgetSumDetailsService,
        CellCommentService $cellCommentService,
        CellCalculationService $cellCalculationService,
        SageNotAssignedDataService $sageNotAssignedDataService,
        SageAssignedDataService $sageAssignedDataService,
    ): RedirectResponse {
        $budgetTemplateController = new BudgetTemplateController($tableService);
        $budgetTemplateController->deleteOldTable(
            $project,
            $mainPositionService,
            $columnService,
            $sumCommentService,
            $sumMoneySourceService,
            $subPositionVerifiedService,
            $subPositionSumDetailService,
            $subPositionRowService,
            $rowCommentService,
            $columnCellService,
            $mainPositionVerifiedService,
            $mainPositionDetailsService,
            $subPositionService,
            $budgetSumDetailsService,
            $cellCommentService,
            $cellCalculationService,
            $sageNotAssignedDataService,
            $sageAssignedDataService
        );
        $this->budgetService->generateBasicBudgetValues(
            $project,
        );

        return Redirect::back();
    }

    public function verifiedMainPosition(Request $request): RedirectResponse
    {
        $mainPosition = MainPosition::find($request->mainPositionId);
        $this->setMainPositionCellVerifiedValue($mainPosition);
        $mainPosition->update(['is_verified' => 'BUDGET_VERIFIED_TYPE_CLOSED']);
        $verifiedRequest = $mainPosition->verified()->first();


        DatabaseNotification::query()
            ->whereJsonContains("data->budgetData->position_id", $mainPosition->id)
            ->whereJsonContains("data->budgetData->requested_by", $verifiedRequest->requested)
            ->whereJsonContains("data->budgetData->changeType", BudgetTypeEnum::BUDGET_VERIFICATION_REQUEST)
            ->delete();

        $this->changeService->saveFromBuilder(
            $this->changeService
                ->createBuilder()
                ->setType('budget')
                ->setModelClass(Project::class)
                ->setModelId($request->project_id)
                ->setTranslationKey('Main position verified')
                ->setTranslationKeyPlaceholderValues([$mainPosition->name])
        );

        return Redirect::back();
    }

    private function setSubPositionCellVerifiedValue(SubPosition $subPosition): void
    {
        $subPositionRows = $subPosition->subPositionRows()->get();
        foreach ($subPositionRows as $subPositionRow) {
            $cells = $subPositionRow->cells()->get();
            foreach ($cells as $cell) {
                $cell->update(['verified_value' => $cell->value]);
            }
        }
    }

    private function removeSubPositionCellVerifiedValue(SubPosition $subPosition): void
    {
        $subPositionRows = $subPosition->subPositionRows()->get();
        foreach ($subPositionRows as $subPositionRow) {
            $cells = $subPositionRow->cells()->get();
            foreach ($cells as $cell) {
                $cell->update(['verified_value' => null]);
            }
        }
    }

    private function setMainPositionCellVerifiedValue(MainPosition $mainPosition): void
    {
        $subPositions = $mainPosition->subPositions()->get();
        foreach ($subPositions as $subPosition) {
            $subPositionRows = $subPosition->subPositionRows()->get();
            foreach ($subPositionRows as $subPositionRow) {
                $cells = $subPositionRow->cells()->get();
                foreach ($cells as $cell) {
                    $cell->update(['verified_value' => $cell->value]);
                }
            }
        }
    }

    private function removeMainPositionCellVerifiedValue(MainPosition $mainPosition): void
    {
        $subPositions = $mainPosition->subPositions()->get();
        foreach ($subPositions as $subPosition) {
            $subPositionRows = $subPosition->subPositionRows()->get();
            foreach ($subPositionRows as $subPositionRow) {
                $cells = $subPositionRow->cells()->get();
                foreach ($cells as $cell) {
                    $cell->update(['verified_value' => null]);
                }
            }
        }
    }

    public function updateCellSource(
        Request $request,
        MoneySourceThresholdReminderService $moneySourceThresholdReminderService,
        MoneySourceCalculationService $moneySourceCalculationService
    ): void {
        ColumnCell::find($request->cell_id)
            ->update([
                'linked_type' => $request->linked_type,
                'linked_money_source_id' => $request->money_source_id
            ]);

        if ($request->money_source_id) {
            $moneySourceThresholdReminderService
                ->handleThresholdReminders(
                    MoneySource::find($request->money_source_id),
                    $moneySourceCalculationService,
                    $this->notificationService
                );
        }
    }

    public function updateColumnName(Request $request): void
    {
        $column = Column::find($request->column_id);
        $column->update([
            'name' => $request->columnName
        ]);
    }
    public function updateTableName(Request $request): void
    {
        $table = Table::find($request->table_id);
        $table->update([
            'name' => $request->table_name
        ]);
    }

    public function columnDelete(
        Column $column,
        ColumnService $columnService,
        SumCommentService $sumCommentService,
        SumMoneySourceService $sumMoneySourceService,
        MainPositionDetailsService $mainPositionDetailsService,
        SubPositionSumDetailService $subPositionSumDetailService,
        BudgetSumDetailsService $budgetSumDetailsService,
        ColumnCellService $columnCellService,
        CellCommentService $cellCommentService,
        CellCalculationService $cellCalculationService,
        SageNotAssignedDataService $sageNotAssignedDataService,
        SageAssignedDataService $sageAssignedDataService
    ): RedirectResponse {
        $columnService->forceDelete(
            $column,
            $sumCommentService,
            $sumMoneySourceService,
            $mainPositionDetailsService,
            $subPositionSumDetailService,
            $budgetSumDetailsService,
            $columnCellService,
            $cellCommentService,
            $cellCalculationService,
            $sageNotAssignedDataService,
            $sageAssignedDataService
        );

        return Redirect::back();
    }

    public function updateMainPositionName(Request $request): void
    {
        $mainPosition = MainPosition::find($request->mainPosition_id);
        $mainPosition->update([
            'name' => $request->mainPositionName
        ]);
    }

    public function updateSubPositionName(Request $request): void
    {
        $subPosition = subPosition::find($request->subPosition_id);
        $subPosition->update([
            'name' => $request->subPositionName
        ]);
    }

    private function setColumnSubName(int $table_id): void
    {
        $table = Table::find($table_id);

        // Spalten abrufen und nach Position sortieren
        $columns = $table->columns()->orderBy('position')->get();

        // Nur Spalten, die einen subName bekommen sollen (nach den ersten 3)
        $filteredColumns = $columns->skip(3)->values();

        $totalColumns = $filteredColumns->count(); // Anzahl der betroffenen Spalten
        $count = 1;

        foreach ($filteredColumns as $column) {
            // Wenn es die Spalte mit Position 100 ist, bekommt sie den letzten Buchstaben
            $subName = ($column->position == 100) ? $this->getNameFromNumber($totalColumns) : $this->getNameFromNumber($count);

            $column->update(['subName' => $subName]);

            $count++;
        }
    }

    public function getNameFromNumber(int $num): string
    {
        $num--; // Da A = 0 ist
        $letters = '';

        while ($num >= 0) {
            $letters = chr(65 + ($num % 26)) . $letters;
            $num = intdiv($num, 26) - 1;
        }

        return $letters;
    }




    public function addColumn(Request $request): void
    {
        $table = Table::find($request->table_id);
        if ($request->column_type === 'empty') {
            /** @var Column $column */
            $column = $table->columns()->create([
                'name' => 'empty',
                'subName' => '-',
                'type' => 'empty',
                'linked_first_column' => null,
                'linked_second_column' => null,
                'position' => $table->columns()->whereNot('position', 100)->max('position') + 1
            ]);
            $this->setColumnSubName($request->table_id);

            $subPositionRows = SubPositionRow::whereHas(
                'subPosition.mainPosition',
                function (Builder $query) use ($request): void {
                    $query->where('table_id', $request->table_id);
                }
            )->get();

            foreach ($subPositionRows as $subPositionRow) {
                $column->cells()->create(
                    [
                        'column_id' => $column->id,
                        'sub_position_row_id' => $subPositionRow->id,
                        'value' => 0,
                        'verified_value' => null,
                        'linked_money_source_id' => null,
                        'commented' => $subPositionRow->commented
                    ]
                );
            }

            $subPositions = SubPosition::whereHas('mainPosition', function (Builder $query) use ($request): void {
                $query->where('table_id', $request->table_id);
            })->get();


            $column->subPositionSumDetails()->createMany(
                $subPositions->map(fn($subPosition) => [
                    'sub_position_id' => $subPosition->id
                ])->all()
            );

            $mainPositions = MainPosition::where('table_id', $request->table_id)->get();

            $column->mainPositionSumDetails()->createMany(
                $mainPositions->map(fn($mainPosition) => [
                    'main_position_id' => $mainPosition->id
                ])->all()
            );

            $column->budgetSumDetails()->create([
                'type' => 'COST'
            ]);

            $column->budgetSumDetails()->create([
                'type' => 'EARNING'
            ]);
        }
        if ($request->column_type === 'sum') {
            $firstColumns = ColumnCell::where('column_id', $request->first_column_id)->get();
            $column = $table->columns()->create([
                'name' => 'sum',
                'subName' => '-',
                'type' => 'sum',
                'linked_first_column' => $request->first_column_id,
                'linked_second_column' => $request->second_column_id,
                'position' => $table->columns()->whereNot('position', 100)->max('position') + 1
            ]);
            $this->setColumnSubName($request->table_id);
            foreach ($firstColumns as $firstColumn) {
                $secondColumn = ColumnCell::where('column_id', $request->second_column_id)
                    ->where('sub_position_row_id', $firstColumn->sub_position_row_id)
                    ->first();
                $firstDecimal = str_replace(',', '.', $firstColumn->value ?: '0');
                $secondDecimal = str_replace(',', '.', $secondColumn->value ?: '0');
                $sum = bcadd($firstDecimal, $secondDecimal, 2);
                ColumnCell::create([
                    'column_id' => $column->id,
                    'sub_position_row_id' => $firstColumn->sub_position_row_id,
                    'value' => $sum,
                    'verified_value' => null,
                    'linked_money_source_id' => null,
                    'commented' => $secondColumn->commented
                ]);
            }
        }

        if ($request->column_type === 'difference') {
            $firstColumns = ColumnCell::where('column_id', $request->first_column_id)->get();
            $column = $table->columns()->create([
                'name' => 'difference',
                'subName' => '-',
                'type' => 'difference',
                'linked_first_column' => $request->first_column_id,
                'linked_second_column' => $request->second_column_id,
                'position' => $table->columns()->whereNot('position', 100)->max('position') + 1
            ]);
            $this->setColumnSubName($request->table_id);
            foreach ($firstColumns as $firstColumn) {
                $secondColumn = ColumnCell::where('column_id', $request->second_column_id)
                    ->where('sub_position_row_id', $firstColumn->sub_position_row_id)
                    ->first();
                $firstDecimal = str_replace(',', '.', $firstColumn->value ?: '0');
                $secondDecimal = str_replace(',', '.', $secondColumn->value ?: '0');
                $sum = bcsub($firstDecimal, $secondDecimal, 2);
                ColumnCell::create([
                    'column_id' => $column->id,
                    'sub_position_row_id' => $firstColumn->sub_position_row_id,
                    'value' => $sum,
                    'verified_value' => null,
                    'linked_money_source_id' => null,
                    'commented' => $secondColumn->commented
                ]);
            }
        }
    }

    public function updateCellValue(
        Request $request,
        MoneySourceThresholdReminderService $moneySourceThresholdReminderService,
        MoneySourceCalculationService $moneySourceCalculationService
    ): void {
        $column = Column::find($request->column_id);
        $project = $column->table()->first()->project()->first();
        $cell = ColumnCell::where('column_id', $request->column_id)
            ->where('sub_position_row_id', $request->sub_position_row_id)
            ->first();

        if ($request->is_verified) {
            $this->changeService->saveFromBuilder(
                $this->changeService
                    ->createBuilder()
                    ->setType('budget')
                    ->setModelClass(Project::class)
                    ->setModelId($project->id)
                    ->setTranslationKey('Cell value changed')
                    ->setTranslationKeyPlaceholderValues([
                        $cell->value,
                        $request->value
                    ])
            );
        }

        $cell->update(['value' => $request->value]);
        $this->updateAutomaticCellValues($request->sub_position_row_id);

        if ($cell->linked_money_source_id) {
            $moneySourceThresholdReminderService->handleThresholdReminders(
                MoneySource::find($cell->linked_money_source_id),
                $moneySourceCalculationService,
                $this->notificationService
            );
        }
    }

    public function changeColumnColor(Request $request): RedirectResponse
    {
        $column = Column::find($request->columnId);
        $column->update(['color' => $request->color]);
        return Redirect::back();
    }

    public function addSubPositionRow(Request $request): void
    {
        $table = Table::find($request->table_id);
        $columns = $table->columns()->get();
        $subPosition = SubPosition::find($request->sub_position_id);

        SubPositionRow::query()
            ->where('sub_position_id', $request->sub_position_id)
            ->where('position', '>', $request->positionBefore)
            ->increment('position');

        /** @var SubPositionRow $subPositionRow */
        $subPositionRow = $subPosition->subPositionRows()->create([
            'commented' => false,
            'position' => $request->positionBefore + 1
        ]);

        $firstThreeColumns = $columns->shift(3);

        foreach ($firstThreeColumns as $firstThreeColumn) {
            $subPositionRow->cells()->create([
                'column_id' => $firstThreeColumn->id,
                'sub_position_row_id' => $subPositionRow->id,
                'value' => '-',
                'linked_money_source_id' => null,
                'verified_value' => ''
            ]);
        }

        foreach ($columns as $column) {
            $subPositionRow->cells()->create([
                'column_id' => $column->id,
                'sub_position_row_id' => $subPositionRow->id,
                'value' => '0,00',
                'linked_money_source_id' => null,
                'verified_value' => ''
            ]);
        }
    }

    public function dropSageData(
        Request $request,
    ): void {
        $this->sage100Service->dropData($request);
    }

    public function addMainPosition(Request $request): void
    {
        $table = Table::find($request->table_id);
        $columns = $table->columns()->get();

        MainPosition::query()
            ->where('table_id', $request->table_id)
            ->where('position', '>', $request->positionBefore)
            ->increment('position');

        $mainPosition = $table->mainPositions()->create([
            'type' => $request->type,
            'name' => 'Neue Hauptposition',
            'position' => $request->positionBefore + 1
        ]);

        $subPosition = $mainPosition->subPositions()->create([
            'name' => 'Neue Unterposition',
            'position' => $mainPosition->subPositions()->max('position') + 1
        ]);

        $mainPosition->mainPositionSumDetails()->createMany(
            $columns->map(fn($column) => [
                'column_id' => $column->id
            ])->all()
        );

        $this->addSubPositionRowsWithColumns($subPosition, $columns);
    }

    public function addSubPosition(Request $request): void
    {
        $table = Table::find($request->table_id);
        $columns = $table->columns()->get();
        $mainPosition = MainPosition::find($request->main_position_id);

        SubPosition::query()
            ->where('main_position_id', $request->main_position_id)
            ->where('position', '>', $request->positionBefore)
            ->increment('position');

        $subPosition = $mainPosition->subPositions()->create([
            'name' => 'Neue Unterposition',
            'position' => $request->positionBefore + 1
        ]);

        $this->addSubPositionRowsWithColumns($subPosition, $columns);
    }

    private function addSubPositionRowsWithColumns(SubPosition $subPosition, Collection $columns): void
    {
        /** @var SubPositionRow $subPositionRow */
        $subPositionRow = $subPosition->subPositionRows()->create([
            'commented' => false,
            'position' => 1,
        ]);

        $firstThreeColumns = $columns->shift(3);

        foreach ($firstThreeColumns as $firstThreeColumn) {
            $subPositionRow->cells()->create([
                'column_id' => $firstThreeColumn->id,
                'sub_position_row_id' => $subPositionRow->id,
                'value' => '0,00',
                'linked_money_source_id' => null,
                'verified_value' => ''
            ]);
        }

        $subPosition->subPositionSumDetails()->createMany(
            $columns->map(fn($column) => [
                'column_id' => $column->id
            ])->all()
        );

        foreach ($columns as $column) {
            $subPositionRow->cells()->create([
                'column_id' => $column->id,
                'sub_position_row_id' => $subPositionRow->id,
                'value' => '0,00',
                'linked_money_source_id' => null,
                'verified_value' => ''
            ]);
        }
    }

    public function updateCellCalculation(Request $request): RedirectResponse
    {
        if ($request->calculations) {
            foreach ($request->calculations as $calculation) {
                $cellCalculation = CellCalculation::find($calculation['id']);
                $cellCalculation->update([
                    'name' => $calculation['name'] ?? '',
                    'value' => $calculation['value'] ?? 0,
                    'description' => $calculation['description'] ?? ''
                ]);
            }

            $cell = ColumnCell::find($request->calculations[0]['cell_id']);
            $cell->update(['value' => $cell->calculations()->sum('value')]);
        }

        return Redirect::back();
    }

    public function addCalculation(ColumnCell $cell, Request $request): void
    {
        $position = $request->integer('position');

        $newCalculation = $cell->calculations()->create([
            'name' => '',
            'value' => 0,
            'description' => '',
            'position' => $position + 1
        ]);

        // update all positions of calculations where position is greater than $request->position and cell_id is
        // $cell->id and where not id is $newCalculation->id, increment position by 1 after new calculation
        CellCalculation::query()
            ->where('cell_id', $cell->id)
            ->where('position', '>', $position)
            ->where('id', '!=', $newCalculation->id)
            ->increment('position');
    }

    /**
     * This function automatically recalculates the linked columns when changes are made.
     * @param $subPositionRowId
     * @return void
     */
    private function updateAutomaticCellValues($subPositionRowId): void
    {

        $rows = ColumnCell::where('sub_position_row_id', $subPositionRowId)->get();

        foreach ($rows as $row) {
            $column = Column::find($row->column_id);

            if ($column->type === 'empty' || $column->type === 'sage') {
                continue;
            }
            $firstRowValue = ColumnCell::where('column_id', $column->linked_first_column)
                ->where('sub_position_row_id', $subPositionRowId)
                ->first()
                ?->value;
            $secondRowValue = ColumnCell::where('column_id', $column->linked_second_column)
                ->where('sub_position_row_id', $subPositionRowId)
                ->first()
                ?->value;
            $updateColumn = ColumnCell::where('sub_position_row_id', $subPositionRowId)
                ->where('column_id', $column->id)
                ->first();

            if ($column->type === 'sum') {
                $firstDecimal = str_replace(',', '.', $firstRowValue ?: '0');
                $secondDecimal = str_replace(',', '.', $secondRowValue ?: '0');
                $sum = bcadd($firstDecimal, $secondDecimal, 2);
                $updateColumn->update([
                    'value' => $sum
                ]);
            } else {
                $firstDecimal = str_replace(',', '.', $firstRowValue ?: '0');
                $secondDecimal = str_replace(',', '.', $secondRowValue ?: '0');
                $sum = bcsub($firstDecimal, $secondDecimal, 2);
                $updateColumn->update([
                    'value' => $sum
                ]);
            }
        }
    }

    public function lockColumn(Request $request): RedirectResponse
    {
        $column = Column::find($request->columnId);
        $column->update(['is_locked' => true, 'locked_by' => Auth::id()]);
        return Redirect::back();
    }

    public function unlockColumn(Request $request): RedirectResponse
    {
        $column = Column::find($request->columnId);
        $column->update(['is_locked' => false, 'locked_by' => null]);
        return Redirect::back();
    }

    public function updateProjectState(Request $request, Project $project): void
    {
        // Hole alte State-ID bevor wir updaten
        $oldStateId = optional($project->state);



        // Aktualisiere nur, wenn wirklich eine neue State-ID kommt
        // (kleine Absicherung, damit wir nicht sinnlos speichern)
        $newStateId = $request->input('state');

        if ($newStateId !== null && (int) $project->state !== (int) $newStateId) {
            $project->update([
                'state' => $newStateId,
            ]);
        }

        // Hole neuen State nach dem Update aus der Relation
        $newStateIdAfterSave = optional($project->state);

        // Hat sich der Status wirklich geändert?
        $stateChanged = $oldStateId !== $newStateIdAfterSave;


        if ($stateChanged) {
            $this->changeService->saveFromBuilder(
                $this->changeService
                    ->createBuilder()
                    ->setType('public_changes')
                    ->setModelClass(Project::class)
                    ->setModelId($project->id)
                    ->setTranslationKey('Project status has changed')
            );

            // Nur wenn sich was geändert hat, verschicken wir auch die Notification
            $this->setPublicChangesNotification($project->id);
        }
    }


    /**
     * @throws Throwable
     */
    //@todo: fix phpcs error - refactor function because complexity exceeds allowed maximum
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded


    /**
     * @throws Throwable
     * @throws \JsonException
     */
    public function projectTab(
        Request $request,
        Project $project,
        ProjectTab $projectTab,
        SageAssignedDataCommentService $sageAssignedDataCommentService,
        ShiftQualificationService $shiftQualificationService,
        RoomService $roomService,
        SageApiSettingsService $sageApiSettingsService,
        ContractTypeService $contractTypeService,
        CompanyTypeService $companyTypeService,
        CurrencyService $currencyService,
        CollectingSocietyService $collectingSocietyService,
        ProjectService $projectService,
        UserService $userService,
        FreelancerService $freelancerService,
        ServiceProviderService $serviceProviderService,
        CraftService $craftService,
        CalendarService $calendarService,
        FilterService $filterService,
        EventTypeService $eventTypeService,
        AreaService $areaService,
        EventService $eventService,
        ProjectCreateSettings $projectCreateSettings,
        EventPropertyService $eventPropertyService
    ): Response|ResponseFactory {
        // Header-Objekt initialisieren (wird von Frontend-Komponenten erwartet)
        $headerObject = new stdClass();
        $headerObject->project = $project;
        // explizit setzen, wie zuvor verwendet
        $headerObject->project->cost_center = $project->costCenter;

        $loadedProjectInformation = [];

        /** @var User|null $authUser */
        $authUser = $userService->getAuthUser();
        if ($authUser?->last_project_id !== $project->id) {
            $authUser?->update(['last_project_id' => $project->id]);
        }

        $firstEvent = $project->events()->orderBy('start_time', 'ASC')->first();
        $lastEvent = $project->events()->orderBy('end_time', 'DESC')->first();

        // Tab + benötigte Relationen laden (inkl. Sortierung)
        $projectTab->load([
            // Hauptkomponenten inkl. ProjectValue
            'components.component.projectValue' => function ($query) use ($project): void {
                $query->where('project_id', $project->id);
            },
            'components' => function ($query): void {
                $query->orderBy('order');
            },
            // WICHTIG: Berechtigungsdaten der Hauptkomponenten
            'components.component.users',
            'components.component.departments.users',

            // Sidebar-Komponenten inkl. ProjectValue
            'sidebarTabs.componentsInSidebar.component.projectValue' => function ($query) use ($project): void {
                $query->where('project_id', $project->id);
            },
            // WICHTIG: Berechtigungsdaten der Sidebar-Komponenten
            'sidebarTabs.componentsInSidebar.component.users',
            'sidebarTabs.componentsInSidebar.component.departments.users',

            // Disclosure-Komponenten (falls angezeigt)
            'components.disclosureComponents.component.projectValue' => function ($query) use ($project): void {
                $query->where('project_id', $project->id);
            },
            'components.disclosureComponents.component.users',
            'components.disclosureComponents.component.departments.users',
        ]);

        // Alle Komponenten des Tabs inkl. Sidebar (unique, Reihenfolge beibehalten)
        $projectTabComponents = $projectTab
            ->components()
            ->with('component')
            ->get()
            ->concat(
                $projectTab->sidebarTabs->flatMap->componentsInSidebar->unique('id')
            );

        foreach ($projectTabComponents as $componentInTab) {
            $component = $componentInTab->component;

            if ($component->type == ProjectTabComponentEnum::SHIFT_TAB->value) {
                $this->singleShiftPresetService->shareSingleShiftPresets();
            }
        }

        // Gruppenausgabe, Historie & weitere Metadaten füllen
        $groupOutput = $this->getGroupOutput($project);

        $headerObject->firstEventInProject = $firstEvent;
        $headerObject->lastEventInProject  = $lastEvent;

        $headerObject->roomsWithAudience   = Room::withAudience($project->id)->pluck('name', 'id');
        $headerObject->eventTypes          = $this->eventTypeService->getAll();
        $headerObject->states              = $this->projectStateService->getAll();

        $headerObject->projectGroups       = $project->groups;
        $headerObject->groupProjects       = Project::where('is_group', 1)->get();
        $headerObject->projectsOfGroup     = $project->projectsOfGroup()->get();

        $headerObject->categories          = $this->categoryService->getAll();
        $headerObject->projectCategories   = $project->categories;

        $headerObject->genres              = $this->genreService->getAll();
        $headerObject->projectGenres       = $project->genres;

        $headerObject->sectors             = $this->sectorService->getAll();
        $headerObject->rooms               = $this->roomService->getAllWithoutTrashed(); //Scheint wohl nicht gebraucht zu sein
        $headerObject->projectSectors      = $project->sectors;

        $headerObject->projectState        = $project->state;

        $tabInformation = [];
        ProjectTab::orderBy('order')->get()->each(function ($tab) use (&$tabInformation){
            $tabInformation[] = ['id' => $tab->id, 'name' => $tab->name];
        });

        $headerObject->tabs  = $tabInformation;

        $headerObject->currentTabId        = $projectTab->id;
        $headerObject->currentGroup        = $groupOutput;

        // IDs über Pivot-Tabellen beibehalten (wie bisher)
        $headerObject->projectManagerIds   = $project->managerUsers()->pluck('user_id');
        $headerObject->projectWriteIds     = $project->writeUsers()->pluck('user_id');
        $headerObject->projectDeleteIds    = $project->delete_permission_users()->pluck('user_id');

        $headerObject->projectCategoryIds  = $project->categories()->pluck('category_id');
        $headerObject->projectGenreIds     = $project->genres()->pluck('genre_id');
        $headerObject->projectSectorIds    = $project->sectors()->pluck('sector_id');

        // Achtung: wird an mehreren Stellen gesetzt – Reihenfolge/Name unverändert lassen
        $headerObject->project->project_managers = $project->managerUsers;

        $headerObject->eventStatuses = app(EventSettings::class)->enable_status
            ? EventStatus::orderBy('order')->get()
            : [];

        $headerObject->event_properties = $eventPropertyService->getAll();
        $latestChange = array_map(
            static function ($history) {
                return [
                    'changes'    => json_decode($history->changes, false, 512, JSON_THROW_ON_ERROR),
                    'created_at' => $history->created_at->diffInHours() < 24
                        ? $history->created_at->diffForHumans()
                        : $history->created_at->format('d.m.Y, H:i'),
                    'changer'    => $history->changer()
                        ->without(['roles', 'departments', 'calendar_settings', 'calendarAbo', 'shiftCalendarAbo'])
                        ->first(),
                ];
            },
            [$project->historyChanges()->first()]
        );
        $headerObject->project_history = $latestChange;

        return inertia('Projects/Tab/TabContent', [
            'currentTab'                  => $projectTab,
            'headerObject'                => $headerObject,
            'loadedProjectInformation'    => $loadedProjectInformation,
            'first_project_tab_id'        => $this->projectTabService->getFirstProjectTabId(),
            'first_project_calendar_tab_id' => $this->projectTabService
                ->getFirstProjectTabWithTypeIdOrFirstProjectTabId(ProjectTabComponentEnum::CALENDAR),
            'first_project_budget_tab_id'   => $this->projectTabService
                ->getFirstProjectTabWithTypeIdOrFirstProjectTabId(ProjectTabComponentEnum::BUDGET),
            'createSettings'              => app(ProjectCreateSettings::class),
            'printLayouts'                => $this->projectPrintLayoutService->getAll(),
            'project'                       => $headerObject->project,
        ]);
    }

    public function history(Project $project): JsonResponse
    {
        $historyComplete = $project->historyChanges()->all();
        $history = array_map(
            static function ($history) {
                return [
                    'changes'    => json_decode($history->changes, false, 512, JSON_THROW_ON_ERROR),
                    'created_at' => $history->created_at->diffInHours() < 24
                        ? $history->created_at->diffForHumans()
                        : $history->created_at->format('d.m.Y, H:i'),
                    'changer'    => $history->changer()
                        ->without(['roles', 'departments', 'calendar_settings', 'calendarAbo', 'shiftCalendarAbo'])
                        ->first(),
                ];
            },
            $historyComplete
        );

         $access_budget = $project->access_budget;

        return response()->json([
            'history' => $history,
            'access_budget' => $access_budget,
        ]);
    }

    /**
     * Shift-Tab-Daten für den Header befüllen.
     */
    private function loadShiftTabData(stdClass &$headerObject, Project $project): void
    {
        $headerObject->project->shift_relevant_event_types = $project->shiftRelevantEventTypes;
        $headerObject->shift_tab_available_sortings        = ShiftTabSort::cases();
        $headerObject->project->shift_contacts             = $project->shift_contact;
        $headerObject->project->project_managers           = $project->managerUsers;
        $headerObject->project->shiftDescription           = $project->shift_description;
        $headerObject->project->freelancers                = Freelancer::all();
        $headerObject->project->serviceProviders           = ServiceProvider::without(['contacts'])->get();
    }

    /**
     * Array/Collection der Projekt-User für Budget-Tab.
     *
     * @return \Illuminate\Support\Collection<int,array<string,mixed>>
     */
    private function mapProjectUsers(Project $project): \Illuminate\Support\Collection
    {
        return $project->users->map(
            fn (User $user) => [
                'id'                  => $user->id,
                'first_name'          => $user->first_name,
                'last_name'           => $user->last_name,
                'profile_photo_url'   => $user->profile_photo_url,
                'email'               => $user->email,
                'departments'         => $user->departments,
                'position'            => $user->position,
                'business'            => $user->business,
                'phone_number'        => $user->phone_number,
                'project_management'  => $user->can(PermissionEnum::PROJECT_MANAGEMENT->value),
                'pivot_access_budget' => (bool) ($user->pivot?->access_budget),
                'pivot_is_manager'    => (bool) ($user->pivot?->is_manager),
                'pivot_can_write'     => (bool) ($user->pivot?->can_write),
                'pivot_delete_permission' => (bool) ($user->pivot?->delete_permission),
            ]
        );
    }

    /**
     * Liefert die Gruppen-Instanz (oder null), zu der das Projekt gehört.
     */
    private function getGroupOutput(Project $project): ?Project
    {
        $groupLink = DB::table('project_groups')
            ->select(['group_id'])
            ->where('project_id', $project->id)
            ->first();

        return $groupLink ? Project::find($groupLink->group_id) : null;
    }

    public function addTimeLineRow(Event $event): void
    {
        $startTime = Carbon::parse($event->start_time);
        $endTime = Carbon::parse($event->end_time);
        $startDate = Carbon::parse($event->start_time);
        $endDate = Carbon::parse($event->end_time);

        $startTime->setTime(8, 0, 0);
        $endTime->setTime(9, 0, 0);

        // if event has already a timeline, get the last timeline and add 1 hour to start and end time
        if ($event->timelines()->exists()) {
            $lastTimeline = $event->timelines()
                ->orderBy('start_date', 'DESC')
                ->orderBy('start', 'DESC')
                ->orderBy('end_date', 'DESC')
                ->orderBy('end', 'DESC')
                ->limit(1)
                ->first();
            /** @var Timeline $lastTimeline */
            $startTime = Carbon::parse($lastTimeline->end);
            $endTime = Carbon::parse($lastTimeline->end)->addHour();

            $startDate = $lastTimeline->start_date->format('Y-m-d') === $lastTimeline->end_date->format('Y-m-d') &&
                Carbon::parse($lastTimeline->end) < Carbon::parse($lastTimeline->start) ?
                    $lastTimeline->start_date->addDay() :
                    $lastTimeline->start_date;

            $endDate = $startDate?->copy()->addHour();
        }

        $event->timelines()->create(
            [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'start' => $startTime,
                'end' => $endTime,
                'description' => null
            ]
        );
    }

    public function updateTimeLines(UpdateTimelinesRequest $request): void
    {
        foreach ($request->collect('timelines') as $timeline) {
            $findTimeLine = Timeline::find($timeline['id']);
            $findTimeLine->update([
                'start_date' => $timeline['start_date'],
                'end_date' => $timeline['end_date'],
                'start' => $timeline['start'],
                'end' => $timeline['end'],
                'description' => nl2br($timeline['description_without_html'])
            ]);
            if ($event = $findTimeLine->event()->first()) {
                $event->touchQuietly();
                $this->eventService->save($event);
            }
        }
    }

    public function edit(Project $project): Response|ResponseFactory
    {
        return inertia('Projects/Edit', [
            'project' => new ProjectEditResource($project),
            'users' => User::all(),
            'departments' => Department::all()
        ]);
    }

    public function update(UpdateProjectRequest $request, Project $project): JsonResponse|RedirectResponse
    {
        DB::table('project_groups')->where('project_id', '=', $project->id)->delete();
        if ($request->get('selectedGroup') !== null) {
            $group = Project::find($request->get('selectedGroup')['id']);
            $group->projectsOfGroup()->syncWithoutDetaching($project->id);

            // Ensure the group's is_group flag is set to true
            if (!$group->is_group) {
                $group->is_group = true;
                $group->save();
            }

            // Ensure the project has at least one column marked as relevant for project groups
            $table = $project->table()->first();
            if ($table) {
                $columns = $table->columns()->get();
                if ($columns->where('relevant_for_project_groups', true)->isEmpty()) {
                    // Mark the last column as relevant for project groups
                    $lastColumn = $columns->sortBy('position')->last();
                    if ($lastColumn) {
                        $lastColumn->update(['relevant_for_project_groups' => true]);
                    }
                }
            }
        }

        $oldProjectName = $project->name;
        $oldProjectBudgetDeadline = $project->budget_deadline;
        $oldArtistName = $project->artists;

        $this->projectService->updateProject($project, [
            'name' => $request->string('name'),
            'artists' => $request->string('artists'),
            'budget_deadline' => $request->get('budget_deadline'),
            //'state' => $request->integer('state'),
            'cost_center_id' => $request->string('cost_center') !== null ?
                $this->costCenterService->findOrCreateCostCenter($request->string('cost_center'))?->id : null,
            'icon' => $request->get('icon'),
            'color' => $request->get('color'),
            'marked_as_done' => $request->boolean('marked_as_done'),
        ]);

        $this->projectService->detachManagementUsers($project, true);
        if (!empty($request->assignedUsers)) {
            $this->projectService->attachManagementUsers($project, $request->assignedUsers);
        }

        $this->projectService->syncCategories($project, $request->collect('assignedCategoryIds'));
        $this->projectService->syncSectors($project, $request->collect('assignedSectorIds'));
        $this->projectService->syncGenres($project, $request->collect('assignedGenreIds'));

        $this->updateProjectState($request, $project);

        $newProjectName = $project->name;
        $newProjectBudgetDeadline = $project->budget_deadline;
        $newArtistName = $project->artists;

        // history functions
        $this->checkProjectNameChanges($project->id, $oldProjectName, $newProjectName);
        $this->checkProjectBudgetDeadlineChanges($project->id, $oldProjectBudgetDeadline, $newProjectBudgetDeadline);
        $this->checkProjectArtistChanges($project->id, $oldArtistName, $newArtistName);

        $projectId = $project->id;
        foreach ($project->users->all() as $user) {
            $this->schedulingService->create($user->id, 'PROJECT_CHANGES', 'PROJECTS', $projectId);
        }
        return Redirect::back();
    }



    public function updateTeam(Request $request, Project $project): JsonResponse|RedirectResponse
    {
        if (!Auth::user()->hasRole(RoleEnum::ARTWORK_ADMIN->value)) {
            // authorization
            if (
                !Auth::user()->canAny([
                    PermissionEnum::PROJECT_MANAGEMENT->value,
                    PermissionEnum::ADD_EDIT_OWN_PROJECT->value,
                    PermissionEnum::WRITE_PROJECTS->value
                ]) &&
                $project->access_budget->pluck('id')->doesntContain(Auth::id()) &&
                $project->managerUsers->pluck('id')->doesntContain(Auth::id()) &&
                $project->writeUsers->pluck('id')->doesntContain(Auth::id())
            ) {
                return response()->json(['error' => 'Not authorized to assign users to a project.'], 403);
            }
        }

        $projectManagerBefore = $project->managerUsers()->get();
        $projectBudgetAccessBefore = $project->access_budget()->get();
        $projectUsers = $project->users()->get();
        $oldProjectDepartments = $project->departments()->get();

        $project->users()->sync(collect($request->assigned_user_ids));
        $project->departments()->sync(collect($request->assigned_departments)->pluck('id'));

        $newProjectDepartments = $project->departments()->get();
        $projectUsersAfter = $project->users()->get();
        $projectManagerAfter = $project->managerUsers()->get();
        $projectBudgetAccessAfter = $project->access_budget()->get();

        // history functions
        $this->checkDepartmentChanges($project->id, $oldProjectDepartments, $newProjectDepartments);
        // Get and check project admins, managers and users after update
        $this->createNotificationProjectMemberChanges(
            $project,
            $projectManagerBefore,
            $projectUsers,
            $projectUsersAfter,
            $projectManagerAfter,
            $projectBudgetAccessBefore,
            $projectBudgetAccessAfter
        );

        return Redirect::back();
    }

    public function updateAttributes(Request $request, Project $project): JsonResponse|RedirectResponse
    {
        $oldProjectCategories = $project->categories()->get();
        $project->categories()->sync($request->assignedCategoryIds);
        $this->checkProjectCategoryChanges($project->id, $oldProjectCategories, $project->categories()->get());

        $oldProjectGenres = $project->genres()->get();
        $project->genres()->sync($request->assignedGenreIds);
        $this->checkProjectGenreChanges($project->id, $oldProjectGenres, $project->genres()->get());

        $oldProjectSectors = $project->sectors()->get();
        $project->sectors()->sync($request->assignedSectorIds);
        $this->checkProjectSectorChanges($project->id, $oldProjectSectors, $project->sectors()->get());

        return Redirect::back();
    }

    public function updateDescription(Request $request, Project $project): JsonResponse|RedirectResponse
    {
        $oldDescription = $project->description;

        $project->update([
            'description' => nl2br($request->description)
        ]);

        $project->save();
        $newDescription = $project->description;
        $this->checkProjectDescriptionChanges($project->id, $oldDescription, $newDescription);
        return Redirect::back();
    }

    private function checkProjectSectorChanges($projectId, $oldSectors, $newSectors): void
    {
        $oldSectorIds = [];
        $oldSectorNames = [];
        $newSectorIds = [];

        foreach ($oldSectors as $oldSector) {
            $oldSectorIds[] = $oldSector->id;
            $oldSectorNames[$oldSector->id] = $oldSector->name;
        }

        foreach ($newSectors as $newSector) {
            $newSectorIds[] = $newSector->id;
            if (!in_array($newSector->id, $oldSectorIds)) {
                $this->changeService->saveFromBuilder(
                    $this->changeService
                        ->createBuilder()
                        ->setType('public_changes')
                        ->setModelClass(Project::class)
                        ->setModelId($projectId)
                        ->setTranslationKey('Added area')
                        ->setTranslationKeyPlaceholderValues([$newSector->name])
                );
            }
        }

        foreach ($oldSectorIds as $oldSectorId) {
            if (!in_array($oldSectorId, $newSectorIds)) {
                $this->changeService->saveFromBuilder(
                    $this->changeService
                        ->createBuilder()
                        ->setType('public_changes')
                        ->setModelClass(Project::class)
                        ->setModelId($projectId)
                        ->setTranslationKey('Deleted area')
                        ->setTranslationKeyPlaceholderValues([$oldSectorNames[$oldSectorId]])
                );
            }
        }

        $this->setPublicChangesNotification($projectId);
    }

    public function deleteProjectFromGroup(Project $project, Project $projectGroup): void
    {
        $project->projectsOfGroup()->detach($projectGroup->id);
    }

    public function addProjectsToGroup(Request $request, Project $projectGroup): void
    {
        $projectIdsToAdd = $request->collect('projectIdsToAdd')->pluck('id');
        $projectGroup->projectsOfGroup()->sync($projectIdsToAdd);

        // Ensure the project group's is_group flag is set to true if there are projects to add
        if (!$projectGroup->is_group && count($projectIdsToAdd) > 0) {
            $projectGroup->is_group = true;
            $projectGroup->save();
        }

        // Ensure all projects being added to the group have at least one column marked as relevant for project groups
        foreach ($projectIdsToAdd as $projectId) {
            $project = Project::find($projectId);
            if ($project) {
                $table = $project->table()->first();
                if ($table) {
                    $columns = $table->columns()->get();
                    if ($columns->where('relevant_for_project_groups', true)->isEmpty()) {
                        // Mark the last column as relevant for project groups
                        $lastColumn = $columns->sortBy('position')->last();
                        if ($lastColumn) {
                            $lastColumn->update(['relevant_for_project_groups' => true]);
                        }
                    }
                }
            }
        }
    }

    private function checkProjectGenreChanges($projectId, $oldGenres, $newGenres): void
    {
        $oldGenreIds = [];
        $oldGenreNames = [];
        $newGenreIds = [];

        foreach ($oldGenres as $oldGenre) {
            $oldGenreIds[] = $oldGenre->id;
            $oldGenreNames[$oldGenre->id] = $oldGenre->name;
        }

        foreach ($newGenres as $newGenre) {
            $newGenreIds[] = $newGenre->id;
            if (!in_array($newGenre->id, $oldGenreIds)) {
                $this->changeService->saveFromBuilder(
                    $this->changeService
                        ->createBuilder()
                        ->setType('public_changes')
                        ->setModelClass(Project::class)
                        ->setModelId($projectId)
                        ->setTranslationKey('Added genre')
                        ->setTranslationKeyPlaceholderValues([$newGenre->name])
                );
            }
        }

        foreach ($oldGenreIds as $oldGenreId) {
            if (!in_array($oldGenreId, $newGenreIds)) {
                $this->changeService->saveFromBuilder(
                    $this->changeService
                        ->createBuilder()
                        ->setType('public_changes')
                        ->setModelClass(Project::class)
                        ->setModelId($projectId)
                        ->setTranslationKey('Deleted genre')
                        ->setTranslationKeyPlaceholderValues([$oldGenreNames[$oldGenreId]])
                );
            }
        }

        $this->setPublicChangesNotification($projectId);
    }

    private function checkProjectCategoryChanges($projectId, $oldCategories, $newCategories): void
    {
        $oldCategoryIds = [];
        $oldCategoryNames = [];
        $newCategoryIds = [];

        foreach ($oldCategories as $oldCategory) {
            $oldCategoryIds[] = $oldCategory->id;
            $oldCategoryNames[$oldCategory->id] = $oldCategory->name;
        }

        foreach ($newCategories as $newCategory) {
            $newCategoryIds[] = $newCategory->id;
            if (!in_array($newCategory->id, $oldCategoryIds)) {
                $this->changeService->saveFromBuilder(
                    $this->changeService
                        ->createBuilder()
                        ->setType('public_changes')
                        ->setModelClass(Project::class)
                        ->setModelId($projectId)
                        ->setTranslationKey('Added category')
                        ->setTranslationKeyPlaceholderValues([$newCategory->name])
                );
            }
        }

        foreach ($oldCategoryIds as $oldCategoryId) {
            if (!in_array($oldCategoryId, $newCategoryIds)) {
                $this->changeService->saveFromBuilder(
                    $this->changeService
                        ->createBuilder()
                        ->setType('public_changes')
                        ->setModelClass(Project::class)
                        ->setModelId($projectId)
                        ->setTranslationKey('Deleted category')
                        ->setTranslationKeyPlaceholderValues([$oldCategoryNames[$oldCategoryId]])
                );
            }
        }

        $this->setPublicChangesNotification($projectId);
    }

    private function checkProjectNameChanges($projectId, $oldName, $newName): void
    {
        $oldName = Str::lower($oldName);
        $newName = Str::lower($newName);


        if ($oldName !== $newName) {
            $this->changeService->saveFromBuilder(
                $this->changeService
                    ->createBuilder()
                    ->setType('public_changes')
                    ->setModelClass(Project::class)
                    ->setModelId($projectId)
                    ->setTranslationKey('Project name changed')
            );
            $this->setPublicChangesNotification($projectId);
        }
    }

    private function checkProjectArtistChanges($projectId, $oldArtist, $newArtist): void
    {
        $oldArtist = Str::lower($oldArtist);
        $newArtist = Str::lower($newArtist);


        if ($newArtist !== $oldArtist) {
            $this->changeService->saveFromBuilder(
                $this->changeService
                    ->createBuilder()
                    ->setType('public_changes')
                    ->setModelClass(Project::class)
                    ->setModelId($projectId)
                    ->setTranslationKey('Project artists changed')
            );
            $this->setPublicChangesNotification($projectId);
        }
    }

    private function checkProjectBudgetDeadlineChanges(
        int $projectId,
        string|null $oldProjectBudgetDeadline,
        string|null $newProjectBudgetDeadline
    ): void {
        if ($oldProjectBudgetDeadline !== $newProjectBudgetDeadline) {
            $this->changeService->saveFromBuilder(
                $this->changeService
                    ->createBuilder()
                    ->setType('public_changes')
                    ->setModelClass(Project::class)
                    ->setModelId($projectId)
                    ->setTranslationKey('Project budget deadline changed')
            );
            $this->setPublicChangesNotification($projectId);
        }
    }

    public function setPublicChangesNotification($projectId): void
    {
        $project = Project::find($projectId);
        $projectUsers = $project->users()->get();
        foreach ($projectUsers as $projectUser) {
            $this->schedulingService->create($projectUser->id, 'PUBLIC_CHANGES', 'PROJECTS', $project->id);
        }
    }

    private function checkDepartmentChanges($projectId, $oldDepartments, $newDepartments): void
    {
        $oldDepartmentIds = [];
        $newDepartmentIds = [];
        $oldDepartmentNames = [];
        foreach ($oldDepartments as $oldDepartment) {
            $oldDepartmentIds[] = $oldDepartment->id;
            $oldDepartmentNames[$oldDepartment->id] = $oldDepartment->name;
        }

        foreach ($newDepartments as $newDepartment) {
            $newDepartmentIds[] = $newDepartment->id;
            if (!in_array($newDepartment->id, $oldDepartmentIds)) {
                $this->changeService->saveFromBuilder(
                    $this->changeService
                        ->createBuilder()
                        ->setModelClass(Project::class)
                        ->setModelId($projectId)
                        ->setTranslationKey('Department added to project team')
                        ->setTranslationKeyPlaceholderValues([$newDepartment->name])
                );
            }
        }

        foreach ($oldDepartmentIds as $oldDepartmentId) {
            if (!in_array($oldDepartmentId, $newDepartmentIds)) {
                $this->changeService->saveFromBuilder(
                    $this->changeService
                        ->createBuilder()
                        ->setModelClass(Project::class)
                        ->setModelId($projectId)
                        ->setTranslationKey('Department removed from project team')
                        ->setTranslationKeyPlaceholderValues([$oldDepartmentNames[$oldDepartmentId]])
                );
            }
        }
    }

    private function checkProjectDescriptionChanges($projectId, $oldDescription, $newDescription): void
    {
        if (strlen($newDescription) === null) {
            $this->changeService->saveFromBuilder(
                $this->changeService
                    ->createBuilder()
                    ->setType('public_changes')
                    ->setModelClass(Project::class)
                    ->setModelId($projectId)
                    ->setTranslationKey('Short description deleted')
            );
        }
        if ($oldDescription === null && $newDescription !== null) {
            $this->changeService->saveFromBuilder(
                $this->changeService
                    ->createBuilder()
                    ->setType('public_changes')
                    ->setModelClass(Project::class)
                    ->setModelId($projectId)
                    ->setTranslationKey('Short description added')
            );
        }
        if ($oldDescription !== $newDescription && $oldDescription !== null && strlen($newDescription) !== null) {
            $this->changeService->saveFromBuilder(
                $this->changeService
                    ->createBuilder()
                    ->setType('public_changes')
                    ->setModelClass(Project::class)
                    ->setModelId($projectId)
                    ->setTranslationKey('Short description changed')
            );
        }
        $this->setPublicChangesNotification($projectId);
    }

    //@todo: fix phpcs error - refactor function because complexity exceeds allowed maximum
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded
    private function createNotificationProjectMemberChanges(
        Project $project,
        $projectManagerBefore,
        $projectUsers,
        $projectUsersAfter,
        $projectManagerAfter,
        $projectBudgetAccessBefore,
        $projectBudgetAccessAfter
    ): void {
        $userIdsBefore = [];
        $managerIdsBefore = [];
        $budgetIdsBefore = [];
        $userIdsAfter = [];
        $managerIdsAfter = [];
        $budgetIdsAfter = [];

        foreach ($projectUsers as $projectUser) {
            $userIdsBefore[$projectUser->id] = $projectUser->id;
        }

        foreach ($projectManagerBefore as $managerBefore) {
            $managerIdsBefore[$managerBefore->id] = $managerBefore->id;
            if (in_array($managerBefore->id, $userIdsBefore)) {
                unset($userIdsBefore[$managerBefore->id]);
            }
        }
        foreach ($projectBudgetAccessBefore as $budgetBefore) {
            $budgetIdsBefore[$budgetBefore->id] = $budgetBefore->id;
            if (in_array($budgetBefore->id, $userIdsBefore)) {
                unset($userIdsBefore[$budgetBefore->id]);
            }
        }
        foreach ($projectUsersAfter as $projectUserAfter) {
            $userIdsAfter[$projectUserAfter->id] = $projectUserAfter->id;
        }

        foreach ($projectManagerAfter as $managerAfter) {
            $managerIdsAfter[$managerAfter->id] = $managerAfter->id;
            // if added a new project manager, send notification to this user
            if (!in_array($managerAfter->id, $managerIdsBefore)) {
                $notificationTitle = __('notification.project.leader.add', [
                    'project' => $project->name
                ], $managerAfter->language);
                $broadcastMessage = [
                    'id' => rand(1, 1000000),
                    'type' => 'success',
                    'message' => $notificationTitle
                ];
                $this->notificationService->setTitle($notificationTitle);
                $this->notificationService->setIcon('green');
                $this->notificationService->setPriority(3);
                $this->notificationService->setNotificationConstEnum(NotificationEnum::NOTIFICATION_PROJECT);
                $this->notificationService->setBroadcastMessage($broadcastMessage);
                $this->notificationService->setProjectId($project->id);
                $this->notificationService->setNotificationTo($managerAfter);
                $this->notificationService->createNotification();
            }
            if (in_array($managerAfter->id, $userIdsAfter)) {
                unset($userIdsAfter[$managerAfter->id]);
            }
        }

        foreach ($projectBudgetAccessAfter as $budgetAfter) {
            $budgetIdsAfter[$budgetAfter->id] = $budgetAfter->id;
            // if added a new project manager, send notification to this user
            if (!in_array($budgetAfter->id, $budgetIdsBefore)) {
                $notificationTitle = __('notification.project.budget.add', [
                    'project' => $project->name
                ], $budgetAfter->language);
                $broadcastMessage = [
                    'id' => rand(1, 1000000),
                    'type' => 'success',
                    'message' => $notificationTitle
                ];
                $this->notificationService->setTitle($notificationTitle);
                $this->notificationService->setIcon('green');
                $this->notificationService->setPriority(3);
                $this->notificationService->setNotificationConstEnum(NotificationEnum::NOTIFICATION_PROJECT);
                $this->notificationService->setBroadcastMessage($broadcastMessage);
                $this->notificationService->setProjectId($project->id);
                $this->notificationService->setNotificationTo($budgetAfter);
                $this->notificationService->createNotification();
            }
            if (in_array($budgetAfter->id, $userIdsAfter)) {
                unset($userIdsAfter[$budgetAfter->id]);
            }
        }

        foreach ($managerIdsBefore as $managerBefore) {
            if (!in_array($managerBefore, $managerIdsAfter)) {
                $user = User::find($managerBefore);
                $notificationTitle = __('notification.project.leader.remove', [
                    'project' => $project->name
                ], $user->language);
                $broadcastMessage = [
                    'id' => rand(1, 1000000),
                    'type' => 'error',
                    'message' => $notificationTitle
                ];
                $this->notificationService->setTitle($notificationTitle);
                $this->notificationService->setIcon('red');
                $this->notificationService->setPriority(2);
                $this->notificationService->setNotificationConstEnum(NotificationEnum::NOTIFICATION_PROJECT);
                $this->notificationService->setBroadcastMessage($broadcastMessage);
                $this->notificationService->setProjectId($project->id);
                $this->notificationService->setNotificationTo($user);
                $this->notificationService->createNotification();
            }
        }
        foreach ($budgetIdsBefore as $budgetBefore) {
            if (!in_array($budgetBefore, $budgetIdsAfter)) {
                $user = User::find($budgetBefore);
                $notificationTitle = __('notification.project.budget.remove', [
                    'project' => $project->name
                ], $user->language);
                $broadcastMessage = [
                    'id' => rand(1, 1000000),
                    'type' => 'error',
                    'message' => $notificationTitle
                ];
                $this->notificationService->setTitle($notificationTitle);
                $this->notificationService->setIcon('red');
                $this->notificationService->setPriority(2);
                $this->notificationService->setNotificationConstEnum(NotificationEnum::NOTIFICATION_PROJECT);
                $this->notificationService->setBroadcastMessage($broadcastMessage);
                $this->notificationService->setProjectId($project->id);
                $this->notificationService->setNotificationTo($user);
                $this->notificationService->createNotification();
            }
        }
        foreach ($userIdsAfter as $userIdAfter) {
            if (!in_array($userIdAfter, $userIdsBefore)) {
                $user = User::find($userIdAfter);
                $notificationTitle = __('notification.project.member.add', [
                    'project' => $project->name
                ], $user->language);
                $broadcastMessage = [
                    'id' => rand(1, 1000000),
                    'type' => 'success',
                    'message' => $notificationTitle
                ];
                $this->notificationService->setTitle($notificationTitle);
                $this->notificationService->setIcon('green');
                $this->notificationService->setPriority(3);
                $this->notificationService->setNotificationConstEnum(NotificationEnum::NOTIFICATION_PROJECT);
                $this->notificationService->setBroadcastMessage($broadcastMessage);
                $this->notificationService->setProjectId($project->id);
                $this->notificationService->setNotificationTo($user);
                $this->notificationService->createNotification();

                $this->changeService->saveFromBuilder(
                    $this->changeService
                        ->createBuilder()
                        ->setModelClass(Project::class)
                        ->setModelId($project->id)
                        ->setTranslationKey('User added to project team')
                        ->setTranslationKeyPlaceholderValues([$user->first_name . ' ' . $user->last_name])
                );
            }
        }
        foreach ($userIdsBefore as $userIdBefore) {
            if (!in_array($userIdBefore, $userIdsAfter)) {
                $user = User::find($userIdBefore);
                $notificationTitle = __('notification.project.member.remove', [
                    'project' => $project->name
                ], $user->language);
                $broadcastMessage = [
                    'id' => rand(1, 1000000),
                    'type' => 'success',
                    'message' => $notificationTitle
                ];
                $this->notificationService->setTitle($notificationTitle);
                $this->notificationService->setIcon('red');
                $this->notificationService->setPriority(2);
                $this->notificationService->setNotificationConstEnum(NotificationEnum::NOTIFICATION_PROJECT);
                $this->notificationService->setBroadcastMessage($broadcastMessage);
                $this->notificationService->setProjectId($project->id);
                $this->notificationService->setNotificationTo($user);
                $this->notificationService->createNotification();

                $this->changeService->saveFromBuilder(
                    $this->changeService
                        ->createBuilder()
                        ->setType('public_changes')
                        ->setModelClass(Project::class)
                        ->setModelId($project->id)
                        ->setTranslationKey('User removed from project team')
                        ->setTranslationKeyPlaceholderValues([$user->first_name . ' ' . $user->last_name])
                );
            }
        }
    }

    public function duplicate(
        Project $project,
        Request $request
    ): JsonResponse|RedirectResponse {
        // authorization
        if ($project->users->isNotEmpty() || !Auth::user()->hasRole(RoleEnum::ARTWORK_ADMIN->value)) {
            if (
                !Auth::user()->canAny([
                    PermissionEnum::PROJECT_MANAGEMENT->value,
                    PermissionEnum::ADD_EDIT_OWN_PROJECT->value,
                    PermissionEnum::WRITE_PROJECTS->value
                ]) &&
                $project->access_budget->pluck('id')->doesntContain(Auth::id()) &&
                $project->managerUsers->pluck('id')->doesntContain(Auth::id())
            ) {
                return response()->json(['error' => 'Not authorized to assign users to a project.'], 403);
            }
        }

        if ($project->departments->isNotEmpty()) {
            $project->departments->map(fn($department) => $this->authorize('update', $department));
        }

        $newProject = Project::create([
            'name' => '(Kopie) ' . $project->name,
            'description' => $project->description,
            'number_of_participants' => $project->number_of_participants,
            'cost_center' => $project->cost_center,
            'state' => $project->state,
        ]);

        $this->budgetService->generateBasicBudgetValues(
            $newProject,
        );

        $newProject->users()->attach([Auth::id() => ['access_budget' => true]]);
        $newProject->categories()->sync($project->categories->pluck('id'));
        $newProject->sectors()->sync($project->sectors->pluck('id'));
        $newProject->genres()->sync($project->genres->pluck('id'));
        $newProject->departments()->sync($project->departments->pluck('id'));
        $newProject->users()->sync($project->users->pluck('id'));

        if ($projectTab = $this->projectTabService->findFirstProjectTabWithShiftsComponent()) {
            return Redirect::route('projects.tab', [$newProject->id, $projectTab->id]);
        }

        return redirect()->route('projects', [
            'page' => $request->get('page'),
            'entitiesPerPage' => $request->get('entitiesPerPage'),
            'query' => $request->get('query'),
        ]);
    }

    public function destroy(
        Project $project,
        ShiftsQualificationsService $shiftsQualificationsService,
        ShiftUserService $shiftUserService,
        ShiftFreelancerService $shiftFreelancerService,
        ShiftServiceProviderService $shiftServiceProviderService,
        ChangeService $changeService,
        CommentService $commentService,
        ChecklistService $checklistService,
        ProjectFileService $projectFileService,
        EventService $eventService,
        EventCommentService $eventCommentService,
        TimelineService $timelineService,
        ShiftService $shiftService,
        SubEventService $subEventService,
        NotificationService $notificationService,
        ProjectTabService $projectTabService,
        TaskService $taskService,
        Request $request
    ): RedirectResponse {
        foreach ($project->users()->get() as $user) {
            $notificationTitle = __('notification.project.delete', [
                'project' => $project->name
            ], $user->language);
            $broadcastMessage = [
                'id' => rand(1, 1000000),
                'type' => 'error',
                'message' => $notificationTitle
            ];

            $this->notificationService->setTitle($notificationTitle);
            $this->notificationService->setIcon('red');
            $this->notificationService->setPriority(2);
            $this->notificationService->setNotificationConstEnum(NotificationEnum::NOTIFICATION_PROJECT);
            $this->notificationService->setBroadcastMessage($broadcastMessage);
            $this->notificationService->setProjectId($project->id);
            $this->notificationService->setNotificationTo($user);
            $this->notificationService->createNotification();
        }

        $this->projectService->softDelete(
            $project,
            $shiftsQualificationsService,
            $shiftUserService,
            $shiftFreelancerService,
            $shiftServiceProviderService,
            $changeService,
            $commentService,
            $checklistService,
            $projectFileService,
            $eventService,
            $eventCommentService,
            $timelineService,
            $shiftService,
            $subEventService,
            $notificationService,
            $projectTabService,
            $taskService
        );

        return redirect()->route('projects', [
            'page' => $request->get('page'),
            'entitiesPerPage' => $request->get('entitiesPerPage'),
            'query' => $request->get('query'),
        ]);
    }

    public function forceDelete(
        int $id,
        CommentService $commentService,
        ChecklistService $checklistService,
        EventService $eventService,
        ProjectFileService $projectFileService,
        EventCommentService $eventCommentService,
        TimelineService $timelineService,
        ShiftService $shiftService,
        SubEventService $subEventService,
        NotificationService $notificationService,
        TaskService $taskService
    ): RedirectResponse {

        $events = Event::onlyTrashed()->where('project_id', $id)->get();

        /** @var Project $project */
        $project = Project::onlyTrashed()->findOrFail($id);

        $eventService->forceDeleteAll(
            $events,
            $eventCommentService,
            $timelineService,
            $shiftService,
            $subEventService,
            $notificationService
        );

        if ($project) {
            $this->projectService->forceDelete(
                $project,
                $commentService,
                $checklistService,
                $eventService,
                $projectFileService,
                $eventCommentService,
                $timelineService,
                $shiftService,
                $subEventService,
                $notificationService,
                $taskService
            );
        }

        return Redirect::route('projects.trashed');
    }

    public function restore(
        int $id,
        ShiftsQualificationsService $shiftsQualificationsService,
        ShiftUserService $shiftUserService,
        ShiftFreelancerService $shiftFreelancerService,
        ShiftServiceProviderService $shiftServiceProviderService,
        CommentService $commentService,
        ChecklistService $checklistService,
        ProjectFileService $projectFileService,
        EventService $eventService,
        ChangeService $changeService,
        EventCommentService $eventCommentService,
        TimelineService $timelineService,
        ShiftService $shiftService,
        SubEventService $subEventService,
        TaskService $taskService
    ): RedirectResponse {
        /** @var Project $project */
        $project = Project::onlyTrashed()->findOrFail($id);

        if ($project) {
            $this->projectService->restore(
                $project,
                $shiftsQualificationsService,
                $shiftUserService,
                $shiftFreelancerService,
                $shiftServiceProviderService,
                $commentService,
                $checklistService,
                $projectFileService,
                $eventService,
                $changeService,
                $eventCommentService,
                $timelineService,
                $shiftService,
                $subEventService,
                $taskService
            );
        }
        return Redirect::route('projects.trashed');
    }

    public function getTrashedSettings(): Response|ResponseFactory
    {
        return inertia('Trash/ProjectSettings', [
            'trashed_genres' => Genre::onlyTrashed()->get(),
            'trashed_categories' => Category::onlyTrashed()->get(),
            'trashed_sectors' => Sector::onlyTrashed()->get(),
            'trashed_project_states' => ProjectState::onlyTrashed()->get(),
            'trashed_contract_types' => ContractType::onlyTrashed()->get(),
            'trashed_company_types' => CompanyType::onlyTrashed()->get(),
            'trashed_collecting_societies' => CollectingSociety::onlyTrashed()->get(),
            'trashed_currencies' => Currency::onlyTrashed()->get(),
        ]);
    }

    public function getTrashed(): Response|ResponseFactory
    {
        return inertia('Trash/Projects', [
            'trashed_projects' => ProjectIndexResource::collection(Project::onlyTrashed()->get())->resolve()
        ]);
    }

    public function deleteRow(
        SubPositionRow $subPositionRow,
        SubPositionRowService $subPositionRowService,
        RowCommentService $rowCommentService,
        ColumnCellService $columnCellService,
        CellCommentService $cellCommentService,
        CellCalculationService $cellCalculationService,
        SageNotAssignedDataService $sageNotAssignedDataService,
        SageAssignedDataService $sageAssignedDataService
    ): RedirectResponse {
        $subPositionRowService->forceDelete(
            $subPositionRow,
            $rowCommentService,
            $columnCellService,
            $cellCommentService,
            $cellCalculationService,
            $sageNotAssignedDataService,
            $sageAssignedDataService
        );

        return Redirect::back();
    }

    public function deleteTable(
        Table $table,
        TableService $tableService,
        MainPositionService $mainPositionService,
        ColumnService $columnService,
        SumCommentService $sumCommentService,
        SumMoneySourceService $sumMoneySourceService,
        SubPositionVerifiedService $subPositionVerifiedService,
        SubPositionSumDetailService $subPositionSumDetailService,
        SubPositionRowService $subPositionRowService,
        RowCommentService $rowCommentService,
        ColumnCellService $columnCellService,
        MainPositionVerifiedService $mainPositionVerifiedService,
        MainPositionDetailsService $mainPositionDetailsService,
        SubPositionService $subPositionService,
        BudgetSumDetailsService $budgetSumDetailsService,
        CellCommentService $cellCommentService,
        CellCalculationService $cellCalculationService,
        SageNotAssignedDataService $sageNotAssignedDataService,
        SageAssignedDataService $sageAssignedDataService
    ): RedirectResponse {
        $tableService->forceDelete(
            $table,
            $mainPositionService,
            $columnService,
            $sumCommentService,
            $sumMoneySourceService,
            $subPositionVerifiedService,
            $subPositionSumDetailService,
            $subPositionRowService,
            $rowCommentService,
            $columnCellService,
            $mainPositionVerifiedService,
            $mainPositionDetailsService,
            $subPositionService,
            $budgetSumDetailsService,
            $cellCommentService,
            $cellCalculationService,
            $sageNotAssignedDataService,
            $sageAssignedDataService
        );

        return Redirect::back();
    }

    public function deleteMainPosition(
        MainPosition $mainPosition,
        MainPositionService $mainPositionService,
        SumCommentService $sumCommentService,
        SumMoneySourceService $sumMoneySourceService,
        SubPositionVerifiedService $subPositionVerifiedService,
        SubPositionSumDetailService $subPositionSumDetailService,
        SubPositionRowService $subPositionRowService,
        RowCommentService $rowCommentService,
        ColumnCellService $columnCellService,
        MainPositionVerifiedService $mainPositionVerifiedService,
        MainPositionDetailsService $mainPositionDetailsService,
        SubPositionService $subPositionService,
        CellCommentService $cellCommentService,
        CellCalculationService $cellCalculationService,
        SageNotAssignedDataService $sageNotAssignedDataService,
        SageAssignedDataService $sageAssignedDataService
    ): RedirectResponse {
        $mainPositionService->forceDelete(
            $mainPosition,
            $sumCommentService,
            $sumMoneySourceService,
            $subPositionVerifiedService,
            $subPositionSumDetailService,
            $subPositionRowService,
            $rowCommentService,
            $columnCellService,
            $mainPositionVerifiedService,
            $mainPositionDetailsService,
            $subPositionService,
            $cellCommentService,
            $cellCalculationService,
            $sageNotAssignedDataService,
            $sageAssignedDataService
        );

        return Redirect::back();
    }

    public function deleteSubPosition(
        SubPosition $subPosition,
        SubPositionService $subPositionService,
        SumCommentService $sumCommentService,
        SumMoneySourceService $sumMoneySourceService,
        SubPositionVerifiedService $subPositionVerifiedService,
        SubPositionSumDetailService $subPositionSumDetailService,
        SubPositionRowService $subPositionRowService,
        RowCommentService $rowCommentService,
        ColumnCellService $columnCellService,
        CellCommentService $cellCommentService,
        CellCalculationService $cellCalculationService,
        SageNotAssignedDataService $sageNotAssignedDataService,
        SageAssignedDataService $sageAssignedDataService
    ): RedirectResponse {
        $subPositionService->forceDelete(
            $subPosition,
            $sumCommentService,
            $sumMoneySourceService,
            $subPositionVerifiedService,
            $subPositionSumDetailService,
            $subPositionRowService,
            $rowCommentService,
            $columnCellService,
            $cellCommentService,
            $cellCalculationService,
            $sageNotAssignedDataService,
            $sageAssignedDataService
        );

        return Redirect::back();
    }

    public function updateCommentedStatusOfRow(Request $request, SubPositionRow $row): RedirectResponse
    {
        $row->update(['commented' => $request->commented]);

        $cellIds = $row->cells->skip(3)->pluck('id');

        $row->cells()->whereIntegerInRaw('id', $cellIds)->update(['commented' => $request->commented]);

        return Redirect::back();
    }

    public function updateCommentedStatusOfCell(Request $request, ColumnCell $columnCell): RedirectResponse
    {
        $columnCell->update(['commented' => $request->commented]);
        return Redirect::back();
    }

    public function updateKeyVisual(Request $request, Project $project): RedirectResponse
    {
        $oldKeyVisual = $project->key_visual_path;
        if ($request->file('keyVisual')) {
            $request->validate([
                'keyVisual' => ['max:' . 1_024 * 100]
            ]);

            $file = $request->file('keyVisual');

            $img = Image::make($file);

            /*if ($img->width() < 1080) {
                throw ValidationException::withMessages([
                    'keyVisual' => __('notification.project.key_visual.width')
                ]);
            }*/

            Storage::delete('keyVisual/' . $project->key_visual_path);

            $original_name = $file->getClientOriginalName();
            $basename = Str::random(20) . $original_name;

            $project->key_visual_path = $basename;
            $img->save(Storage::path('public/keyVisual/') . $basename, 100, $file->clientExtension());
            Storage::putFileAs('public/keyVisual', $file, $basename);
        }
        $project->save();

        $newKeyVisual = $project->key_visual_path;

        if ($oldKeyVisual !== $newKeyVisual) {
            $this->changeService->saveFromBuilder(
                $this->changeService
                    ->createBuilder()
                    ->setType('public_changes')
                    ->setModelClass(Project::class)
                    ->setModelId($project->id)
                    ->setTranslationKey('Key visual has been changed')
            );
        }

        if ($newKeyVisual === '') {
            $this->changeService->saveFromBuilder(
                $this->changeService
                    ->createBuilder()
                    ->setType('public_changes')
                    ->setModelClass(Project::class)
                    ->setModelId($project->id)
                    ->setTranslationKey('Key visual has been removed')
            );
        }

        $this->setPublicChangesNotification($project->id);

        return Redirect::back();
    }

    public function downloadKeyVisual(Project $project): StreamedResponse
    {
        return Storage::download('public/keyVisual/' . $project->key_visual_path, $project->key_visual_path);
    }

    public function deleteKeyVisual(Project $project): void
    {
        Storage::delete('public/keyVisual/' . $project->key_visual_path);
        $project->update(['key_visual_path' => null]);
    }

    public function updateShiftDescription(Request $request, Project $project): void
    {
        $project->shift_description = $request->shiftDescription;
        $project->save();
    }

    public function updateShiftContacts(Request $request, Project $project): void
    {
        $project->shift_contact()->sync(collect($request->contactIds));
    }

    public function updateShiftRelevantEventTypes(Request $request, Project $project): void
    {
        $project->shiftRelevantEventTypes()->sync(collect($request->shiftRelevantEventTypeIds));
    }

    public function deleteTimeLineRow(Timeline $timeline, TimelineService $timelineService): void
    {
        $timelineService->forceDelete($timeline);
    }

    public function duplicateColumn(Column $column): void
    {
        $newColumn = $column->replicate();
        $newColumn->save();
        $newColumn->update(['name' => $column->name . ' (Kopie)']);
        $newColumn->cells()->forceDelete();
        $newColumn->cells()->createMany($column->cells()->get()->toArray());
    }

    public function duplicateSubPosition(SubPosition $subPosition, $mainPositionId = null): void
    {
        $newSubPosition = $subPosition->replicate();
        $newSubPosition->save();
        $newSubPosition->update(['name' => $subPosition->name . ' (Kopie)']);

        if ($mainPositionId !== null) {
            $newSubPosition->update(['main_position_id' => $mainPositionId]);
        }

        foreach ($subPosition->subPositionRows()->get() as $subPositionRow) {
            $newSubPositionRow = $subPositionRow->replicate();
            $newSubPositionRow->save();
            $newSubPositionRow->update(
                ['name' => $subPositionRow->name . ' (Kopie)', 'sub_position_id' => $newSubPosition->id]
            );
            $newSubPositionRow->cells()->forceDelete();
            $newSubPositionRow->cells()->createMany($subPositionRow->cells()->get()->toArray());
        }
    }

    public function duplicateMainPosition(MainPosition $mainPosition): void
    {
        $newMainPosition = $mainPosition->replicate();
        $newMainPosition->save();
        $newMainPosition->update(['name' => $mainPosition->name . ' (Kopie)']);

        // duplicate sub positions
        foreach ($mainPosition->subPositions()->get() as $subPosition) {
            $this->duplicateSubPosition($subPosition, $newMainPosition->id);
        }
    }

    public function duplicateRow(SubPositionRow $subPositionRow): void
    {
        $subPositionRowReplicate = $subPositionRow->replicate();
        $subPositionRowReplicate->sub_position_id = $subPositionRow->subPosition->id;
        $subPositionRowReplicate->save();

        foreach ($subPositionRow->cells as $subPositionRowCell) {
            $subPositionRowCellReplicate = $subPositionRowCell->replicate();
            $subPositionRowCellReplicate->sub_position_row_id = $subPositionRowReplicate->id;
            $subPositionRowCellReplicate->save();
        }
    }

    public function updateCommentedStatusOfColumn(Request $request, Column $column): void
    {
        $validated = $request->validate(['commented' => 'required|boolean']);
        $column->update(['commented' => $validated['commented']]);
    }

    public function projectBudgetExport(Project $project): BinaryFileResponse
    {
        return (new BudgetExport($project))
            ->download(
                sprintf(
                    '%s_budget_stand_%s.xlsx',
                    Str::snake($project->name),
                    Carbon::now()->format('d-m-Y_H_i_s')
                )
            )
            ->deleteFileAfterSend();
    }

    public function projectsBudgetByBudgetDeadlineExport(
        string $startBudgetDeadline,
        string $endBudgetDeadline,
        Request $request
    ): BinaryFileResponse|null {
        if ($request->integer('type') === 0) {
            return (new BudgetsByBudgetDeadlineExport($startBudgetDeadline, $endBudgetDeadline))
                ->download(
                    sprintf(
                        'budgets_export_%s-%s_stand_%s.xlsx',
                        $startBudgetDeadline,
                        $endBudgetDeadline,
                        Carbon::now()->format('d-m-Y_H_i_s')
                    )
                )
                ->deleteFileAfterSend();
        }

        if ($request->integer('type') === 1) {
            return (new DetailedBudgetsByBudgetDeadlineExport($startBudgetDeadline, $endBudgetDeadline))
                ->download(
                    sprintf(
                        'detaillierter_budget_export_%s-%s_stand_%s.xlsx',
                        $startBudgetDeadline,
                        $endBudgetDeadline,
                        Carbon::now()->format('d-m-Y_H_i_s')
                    )
                )
                ->deleteFileAfterSend();
        }

        return null;
    }

    public function pin(Project $project, Request $request): RedirectResponse
    {
        $this->projectService->pin($project);
        return redirect()->route('projects', [
            'page' => $request->get('page'),
            'entitiesPerPage' => $request->get('entitiesPerPage'),
            'query' => $request->get('query'),
        ]);
    }

    public function updateCopyright(Request $request, Project $project): RedirectResponse
    {
        $oldCostCenter = $project->cost_center_id;
        if (!empty($request->cost_center_name)) {
            $costCenter = CostCenter::firstOrCreate(['name' => $request->cost_center_name]);
        }
        $project->update([
            'cost_center_id' => $costCenter->id ?? null,
            'own_copyright' => $request->own_copyright,
            'live_music' => $request->live_music,
            'collecting_society_id' => $request->collecting_society_id,
            'law_size' => $request->law_size,
            'cost_center_description' => $request->description,
        ]);

        $this->checkProjectCostCenterChanges($project->id, $oldCostCenter, $costCenter->id ?? null);

        return Redirect::back();
    }

    private function checkProjectCostCenterChanges($projectId, $oldCostCenter, $newCostCenter): void
    {
        if ($newCostCenter === null && $oldCostCenter !== null) {
            $this->changeService->saveFromBuilder(
                $this->changeService
                    ->createBuilder()
                    ->setModelClass(Project::class)
                    ->setModelId($projectId)
                    ->setTranslationKey('Cost center deleted')
            );
        }
        if ($oldCostCenter === null && $newCostCenter !== null) {
            $this->changeService->saveFromBuilder(
                $this->changeService
                    ->createBuilder()
                    ->setModelClass(Project::class)
                    ->setModelId($projectId)
                    ->setTranslationKey('Cost center added')
            );
        }
        if ($oldCostCenter !== $newCostCenter && $oldCostCenter !== null && $newCostCenter !== null) {
            $this->changeService->saveFromBuilder(
                $this->changeService
                    ->createBuilder()
                    ->setModelClass(Project::class)
                    ->setModelId($projectId)
                    ->setTranslationKey('Cost center changed')
            );
        }
    }

    public function scoutSearch(Request $request): Collection
    {
        $projects = collect();
        if (
            request()->has('project_search') &&
            request()->get('project_search') !== null &&
            request()->get('project_search') !== ''
        ) {
            $projects = Project::search($request->string('project_search'))
                ->get()
                ->map(function (Project $project) use ($request) {
                    $returnProject =  [
                        'id' => $project->id,
                        'name' => $project->name,
                        'key_visual_path' => $project->key_visual_path,
                        'is_group' => $project->is_group,
                        'marked_as_done' => $project->getAttribute('marked_as_done'),
                    ];

                    $addEventsToReturnProject = [];
                    if ($request->boolean('get_first_last_event')) {
                        $addEventsToReturnProject = [
                        'first_event' => $this->projectService->getFirstEventInProject($project),
                        'last_event' => $this->projectService->getLastEventInProject($project),
                        ];
                    }

                    return array_merge($returnProject, $addEventsToReturnProject);
                });
        }

        return $projects;
    }

    public function updateTimeline(Timeline $timeline, UpdateTimelineRequest $request): void
    {
        $this->timelineService->updateTimeline($timeline, collect($request->all()));
    }
}
