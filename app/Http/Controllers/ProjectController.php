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
use Artwork\Modules\Budget\Services\BudgetCacheService;
use Artwork\Modules\Budget\Services\BudgetService;
use Artwork\Modules\Budget\Services\BudgetSumDetailsService;
use Artwork\Modules\Budget\Services\CellCalculationService;
use Artwork\Modules\Budget\Services\CellCommentService;
use Artwork\Modules\Budget\Services\ColumnCellService;
use Artwork\Modules\Budget\Services\ColumnRelevanceService;
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
use Artwork\Modules\Calendar\DTO\RoomDTO;
use Artwork\Modules\Calendar\Services\CalendarService;
use Artwork\Modules\Calendar\Services\CalendarDataService;
use Artwork\Modules\Calendar\Services\ShiftCalendarService;
use Artwork\Modules\Category\Models\Category;
use Artwork\Modules\Category\Services\CategoryService;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Checklist\Services\ChecklistService;
use Artwork\Modules\CompanyType\Models\CompanyType;
use Artwork\Modules\CompanyType\Services\CompanyTypeService;
use Artwork\Modules\Contract\Models\ContractType;
use Artwork\Modules\Contract\Services\ContractTypeService;
use Artwork\Modules\CostCenter\Models\CostCenter;
use Artwork\Modules\CostCenter\Services\CostCenterService;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Craft\Services\CraftService;
use Artwork\Modules\Currency\Models\Currency;
use Artwork\Modules\Currency\Services\CurrencyService;
use Artwork\Modules\InternalIssue\Models\InternalIssue;
use Artwork\Modules\Inventory\Models\InventoryTag;
use Artwork\Modules\Inventory\Models\InventoryTagGroup;
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
use Artwork\Modules\Project\Events\UpdateBudget;
use Artwork\Modules\Project\Jobs\ForceDeleteProjectJob;
use Artwork\Modules\Project\Jobs\SoftDeleteProjectJob;
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
use Artwork\Modules\Project\Models\ProjectRole;
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
use Artwork\Modules\Project\Events\ProjectTeamUpdated;
use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Room\Services\RoomService;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Sage100\Services\Sage100Service;
use Artwork\Modules\SageApiSettings\Services\SageApiSettingsService;
use Artwork\Modules\Scheduling\Services\SchedulingService;
use Artwork\Modules\Sector\Models\Sector;
use Artwork\Modules\Sector\Services\SectorService;
use Artwork\Modules\ServiceProvider\Enums\ServiceProviderTypes;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\ServiceProvider\Services\ServiceProviderService;
use Artwork\Modules\Shift\Enums\ShiftTabSort;
use Artwork\Modules\Shift\Services\GlobalQualificationService;
use Artwork\Modules\Shift\Services\ShiftGroupService;
use Artwork\Modules\Shift\Services\ShiftFreelancerService;
use Artwork\Modules\Shift\Services\ShiftService;
use Artwork\Modules\Shift\Services\ShiftServiceProviderService;
use Artwork\Modules\Shift\Services\ShiftsQualificationsService;
use Artwork\Modules\Shift\Services\ShiftUserService;
use Artwork\Modules\Shift\Services\ShiftQualificationService;
use Artwork\Modules\Shift\Services\ShiftTimePresetService;
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
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
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
        private readonly \Artwork\Modules\Inventory\Services\InventoryUserFilterShareService $inventoryUserFilterShareService,
        private readonly \Artwork\Modules\BusinessIntelligence\Services\BiProjectMetricsService $biProjectMetricsService,
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
        $userProjectManagementSettingModel = $this->userProjectManagementSettingService
            ->getFromUser($user);

        if (!$userProjectManagementSettingModel) {
            $userProjectManagementSettingModel = $this->userProjectManagementSettingService
                ->updateOrCreateIfNecessary($user, $this->userProjectManagementSettingService->getDefaults());
        }

        $userProjectManagementSetting = $userProjectManagementSettingModel->getAttribute('settings');

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
        // Cache these values outside the loop to avoid N+1 queries
        $firstTabId = $this->projectTabService->getDefaultOrFirstProjectTab();
        $projectStates = ProjectState::all()->keyBy('id');

        // Gruppen-Zähler für den Gruppen-Chip in der Projektübersicht (ein Query statt N+1)
        $projects->loadCount('projectsOfGroup');

        $projectPeriods = $this->prepareProjectsForComponentMapping($projects, $components);

        $mapped = $projects->map(function ($project) use (
            $components,
            $componentData,
            $firstTabId,
            $projectStates,
            $projectPeriods
        ) {
            /** @var Project $project */
            $projectData = new stdClass(); // needed for the ProjectShowHeaderComponent
            $projectData->id = $project->id;
            $projectData->updated_at = $project->updated_at;
            $projectData->firstTabId = $firstTabId;
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
                        $projectData->projects_of_group_count = $project->is_group ? ($project->projects_of_group_count ?? 0) : null;
                        $projectData->color = $project->color;
                        $projectData->icon = $project->icon;
                        break;
                    case ProjectTabComponentEnum::ARTIST_NAME_DISPLAY->value:
                        $projectData->artist_name = $project->artists;
                        break;
                    case ProjectTabComponentEnum::BI_KEY_FIGURES->value:
                        $tickets = $this->biProjectMetricsService->soldTickets($project);
                        $capacity = $this->biProjectMetricsService->seatsCapacity($project);
                        $projectData->bi_key_figures = [
                            'visitors' => $this->biProjectMetricsService->visitors($project),
                            'revenue' => $this->biProjectMetricsService->revenue($project),
                            'occupancy' => $this->biProjectMetricsService->occupancyRate($tickets, $capacity),
                        ];
                        break;
                    case ProjectTabComponentEnum::PROJECT_STATUS->value:
                        $projectData->state = $projectStates->get($project->state);
                        break;
                    case ProjectTabComponentEnum::PROJECT_GROUP->value:
                        $projectData->group = $project->groups;
                        $projectData->is_group = $project->is_group;
                        $projectData->projects_of_group_count = $project->is_group ? ($project->projects_of_group_count ?? 0) : null;
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
                    case ProjectTabComponentEnum::PROJECT_PERIOD->value:
                        $projectData->project_period = $projectPeriods[$project->id] ?? null;
                        break;
                    case ProjectTabComponentEnum::BUDGET_INFORMATIONS->value:
                        $projectData->cost_center = $project->costCenter;
                        $projectData->gema = $project->gema;
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

        $this->unsetHeavyProjectRelations($projects);

        return $mapped;
    }

    /**
     * Der BI-Kennzahlen-Service rechnet auf den geladenen Relationen; nur laden, wenn der
     * Baustein überhaupt konfiguriert ist. Nach dem Mapping werden die Relationen via
     * unsetHeavyProjectRelations() wieder entfernt, damit sie nicht im Inertia-Payload landen.
     *
     * @return array<int, array{first_event_date: string, last_event_date: string}|null>
     */
    private function prepareProjectsForComponentMapping($projects, $components): array
    {
        $componentTypes = $components->pluck('type');

        if ($componentTypes->contains(ProjectTabComponentEnum::BI_KEY_FIGURES->value)) {
            $projects->loadMissing(['biData', 'biEventData.event', 'biRoomCapacities', 'events.room']);
        }

        return $componentTypes->contains(ProjectTabComponentEnum::PROJECT_PERIOD->value)
            ? $this->getProjectPeriods($projects)
            : [];
    }

    /**
     * Die Projekt-Modelle gehen zusätzlich roh an Inertia (`projects`/`pinnedProjectsAll`).
     * Events-/BI-Relationen werden nur für die Komponenten-Berechnung gebraucht — vor der
     * Serialisierung entfernen, sonst wandern alle Termine jedes Projekts ins Payload.
     */
    private function unsetHeavyProjectRelations($projects): void
    {
        foreach ($projects as $project) {
            $project->unsetRelation('events');
            $project->unsetRelation('biData');
            $project->unsetRelation('biEventData');
            $project->unsetRelation('biRoomCapacities');
        }
    }

    /**
     * Batch-Ersatz für Project::getFirstAndLastEventDateAttribute(): zwei Aggregat-Queries
     * für die ganze Seite statt bis zu vier Queries pro Projekt. Gleiche Semantik: Termine
     * mit event_types.relevant_for_project_period, sonst Fallback auf alle Termine;
     * Soft-Deletes sind über den Event-Global-Scope ausgeschlossen.
     *
     * @return array<int, array{first_event_date: string, last_event_date: string}|null>
     */
    private function getProjectPeriods($projects): array
    {
        $projectIds = $projects->pluck('id')->all();

        if ($projectIds === []) {
            return [];
        }

        $relevantPeriods = Event::query()
            ->join('event_types', 'events.event_type_id', '=', 'event_types.id')
            ->where('event_types.relevant_for_project_period', true)
            ->whereIn('events.project_id', $projectIds)
            ->groupBy('events.project_id')
            ->selectRaw('events.project_id, MIN(events.start_time) as first_start, MAX(events.end_time) as last_end')
            ->get()
            ->keyBy('project_id');

        $fallbackPeriods = Event::query()
            ->whereIn('events.project_id', $projectIds)
            ->groupBy('events.project_id')
            ->selectRaw('events.project_id, MIN(events.start_time) as first_start, MAX(events.end_time) as last_end')
            ->get()
            ->keyBy('project_id');

        $periods = [];
        foreach ($projectIds as $projectId) {
            $period = $relevantPeriods->get($projectId) ?? $fallbackPeriods->get($projectId);
            $periods[$projectId] = $period && $period->first_start && $period->last_end
                ? [
                    'first_event_date' => Carbon::parse($period->first_start)->translatedFormat('d.m.Y H:i'),
                    'last_event_date' => Carbon::parse($period->last_end)->translatedFormat('d.m.Y H:i'),
                ]
                : null;
        }

        return $periods;
    }


    public function updateSettings(
        ProjectCreateSettingsUpdateRequest $request,
        ProjectCreateSettings $settings
    ): RedirectResponse {
        $this->projectSettingsService->store($request, $settings);
        return Redirect::back();
    }

    public function updateArtistResidencySettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'breakfast_deduction_per_day' => 'required|numeric|min:0',
            'artist_residency_do_not_save_default' => 'required|boolean',
            'artist_residency_daily_allowance_default' => 'required|numeric|min:0',
            'artist_residency_name_columns' => 'required|array',
            'artist_residency_name_columns.*.key' => 'required|string|in:name,first_name,last_name',
            'artist_residency_name_columns.*.enabled' => 'required|boolean',
        ]);

        $settings = app(\Artwork\Modules\GeneralSettings\Models\GeneralSettings::class);
        $settings->breakfast_deduction_per_day = (float) $validated['breakfast_deduction_per_day'];
        $settings->artist_residency_do_not_save_default = (bool) $validated['artist_residency_do_not_save_default'];
        $settings->artist_residency_daily_allowance_default =
            (float) $validated['artist_residency_daily_allowance_default'];
        $settings->artist_residency_name_columns = array_map(
            static fn (array $column): array => [
                'key' => $column['key'],
                'enabled' => (bool) $column['enabled'],
            ],
            $validated['artist_residency_name_columns']
        );
        $settings->save();

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
            'users' => UserWithoutShiftsResource::collection(
                User::nameOrLastNameLike($query)->with('defaultProjectRoles')->get()
            )->resolve()
        ];
    }

    /**
     * @return array<string, mixed>
     * @throws AuthorizationException
     */
    public function search(SearchRequest $request, ProjectService $projectService): Collection
    {
        $this->authorize('viewAny', Project::class);

        $projects = $projectService->searchProjectsByNameOrArtists($request->get('query'));

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

    /**
     * Get basic project information including budget_deadline.
     */
    public function showBasic(Project $project): JsonResponse
    {
        $budgetDeadline = $project->budget_deadline;
        if ($budgetDeadline instanceof \Carbon\Carbon) {
            $budgetDeadline = $budgetDeadline->format('Y-m-d');
        }

        return response()->json([
            'id' => $project->id,
            'name' => $project->name,
            'budget_deadline' => $budgetDeadline,
        ]);
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

        $assignedUsers = collect($request->get('assignedUsers'));
        $currentUser = Auth::user();

        // Users without "view all projects" permission should automatically be added to the project team
        // Users with this permission should NOT be automatically added (unless explicitly assigned)
        if (!$currentUser->can(PermissionEnum::PROJECT_VIEW->value)) {
            // User doesn't have global read access, so add them to the project team
            $this->projectService->attachUserToProject($project, $this->authManager->id(), true);
        } elseif ($assignedUsers->contains(Auth::id())) {
            // User has global read access but is explicitly included in assignedUsers
            $this->projectService->attachUserToProject($project, $this->authManager->id(), true);
        }

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

        if (is_array($request->input('crm_artist_contact_ids'))) {
            $this->projectService->syncCrmArtistContacts($project, $request->input('crm_artist_contact_ids'));
        }

        if ($request->boolean('isGroup')) {
            $project->update(['is_group' => true]);
            $project->projectsOfGroup()->sync($request->get('projects'));
        } elseif (!empty($request->selectedGroup)) {
            $group = Project::find($request->selectedGroup['id'] ?? null);
            if ($group) {
                $group->projectsOfGroup()->syncWithoutDetaching($project->id);

                // Ensure the group's is_group flag is set to true
                if (!$group->is_group) {
                    $group->is_group = true;
                    $group->save();
                }
            }
        }

        $this->projectService->syncCategories($project, $request->collect('assignedCategoryIds'), $request->input('mainCategoryId'));
        $this->projectService->syncSectors($project, $request->collect('assignedSectorIds'), $request->input('mainSectorId'));
        $this->projectService->syncGenres($project, $request->collect('assignedGenreIds'), $request->input('mainGenreId'));

        $project->departments()->sync($departments->pluck('id'));

        // Generate basic budget values for the project
        $this->budgetService->generateBasicBudgetValues($project);

        // If the project is not a group but is associated with a group,
        // ensure it has at least one column marked as relevant for project groups
        if (!$request->boolean('isGroup') && !empty($request->selectedGroup)) {
            $table = $project->table()->first();
            if ($table) {
                app(ColumnRelevanceService::class)->ensureSingleRelevantColumn($table);
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
            'position' => $costSubPosition->subPositionRows()->max('position') + 1,
            'order' => $costSubPosition->subPositionRows()->max('order') + 1,
        ]);

        $earningSubPositionRow = $earningSubPosition->subPositionRows()->create([
            'commented' => false,
            'position' => $earningSubPosition->subPositionRows()->max('position') + 1,
            'order' => $earningSubPosition->subPositionRows()->max('order') + 1,

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

    public function verifiedRequestMainPosition(Request $request): void
    {
        $mainPosition = MainPosition::find($request->id);
        $project = $mainPosition->table()->first()->project()->first();

        if ($request->giveBudgetAccess) {
            $project->users()->updateExistingPivot($request->user, ['access_budget' => true]);
            $user = User::find($request->user);
            if ($user !== null) {
                $notificationTitle = __('notification.project.budget.add', ['project' => $project->name], $user->language);
                $broadcastMessage = [
                    'id' => Str::uuid()->toString(),
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
        }
        $mainPosition->update(['is_verified' => BudgetTypeEnum::BUDGET_VERIFIED_TYPE_REQUESTED]);
        $notificationTitle = __(
            'notification.project.budget.new_verify_request',
            [],
            User::find($request->user)?->language ?? 'de'
        );
        $budgetData = new stdClass();
        $budgetData->position_id = $mainPosition->id;
        $budgetData->requested_by = Auth::id();
        $budgetData->changeType = BudgetTypeEnum::BUDGET_VERIFICATION_REQUEST;
        $broadcastMessage = [
            'id' => Str::uuid()->toString(),
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
        $requestedUser = User::find($request->user);
        if ($requestedUser !== null) {
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
            $this->notificationService->setNotificationTo($requestedUser);
            $this->notificationService->createNotification();
        }

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

        broadcast(new UpdateBudget($mainPosition->table->project_id));

        //return Redirect::back();
    }

    public function takeBackVerification(Request $request): void
    {

        $budgetData = new stdClass();
        $budgetData->requested_by = Auth::id();
        $mainPosition = null;
        $budgetData->changeType = BudgetTypeEnum::BUDGET_VERIFICATION_TAKE_BACK;
        if ($request->type === 'main') {
            $mainPosition = MainPosition::find($request->position['id']);
            $verifiedRequest = $mainPosition->verified()->first();
            $requestedUser = User::find($verifiedRequest->requested);
            $notificationTitle = __(
                'notification.project.budget.delete_verify_request',
                [],
                $requestedUser?->language ?? 'de'
            );
            $table = $mainPosition->table()->first();
            $project = $table->project()->first();
            // Delete Function Updated to new Notification System
            $this->deleteOldNotification($mainPosition->id, $verifiedRequest->requested);
            $budgetData->position_id = $mainPosition->id;
            $broadcastMessage = [
                'id' => Str::uuid()->toString(),
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
            if ($requestedUser !== null) {
                $this->notificationService->setTitle($notificationTitle);
                $this->notificationService->setIcon('red');
                $this->notificationService
                    ->setNotificationConstEnum(NotificationEnum::NOTIFICATION_BUDGET_STATE_CHANGED);
                $this->notificationService->setBroadcastMessage($broadcastMessage);
                $this->notificationService->setBudgetData($budgetData);
                $this->notificationService->setProjectId($project->id);
                $this->notificationService->setDescription($notificationDescription);
                $this->notificationService->setNotificationTo($requestedUser);
                $this->notificationService->createNotification();
            }
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
            $requestedUser = User::find($verifiedRequest->requested);
            $notificationTitle = __(
                'notification.project.budget.delete_verify_request',
                [],
                $requestedUser?->language ?? 'de'
            );
            $project = $table->project()->first();
            // Delete Function Updated to new Notification System
            $this->deleteOldNotification($subPosition->id, $verifiedRequest->requested);
            $budgetData->position_id = $mainPosition->id;
            $broadcastMessage = [
                'id' => Str::uuid()->toString(),
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

            if ($requestedUser !== null) {
                $this->notificationService->setTitle($notificationTitle);
                $this->notificationService->setIcon('red');
                $this->notificationService
                    ->setNotificationConstEnum(NotificationEnum::NOTIFICATION_BUDGET_STATE_CHANGED);
                $this->notificationService->setBroadcastMessage($broadcastMessage);
                $this->notificationService->setBudgetData($budgetData);
                $this->notificationService->setProjectId($project->id);
                $this->notificationService->setNotificationTo($requestedUser);
                $this->notificationService->setDescription($notificationDescription);
                $this->notificationService->createNotification();
            }
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

        broadcast(new UpdateBudget($mainPosition->table->project_id));
        //return Redirect::back();
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
    ): void {
        $budgetData = new stdClass();
        $budgetData->requested_by = Auth::id();
        $budgetData->changeType = BudgetTypeEnum::BUDGET_VERIFICATION_DELETED;
        $mainPosition = null;
        if ($request->type === 'main') {
            $mainPosition = MainPosition::find($request->position['id']);
            $verifiedRequest = $mainPosition->verified()->first();
            $requestedUser = User::find($verifiedRequest->requested);
            $notificationTitle = __(
                'notification.project.budget.verify_removed',
                [],
                $requestedUser?->language ?? 'de'
            );
            $this->removeMainPositionCellVerifiedValue($mainPosition);
            $project = $mainPosition->table()->first()->project()->first();
            $budgetData->position_id = $mainPosition->id;
            $broadcastMessage = [
                'id' => Str::uuid()->toString(),
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
            if ($requestedUser !== null) {
                $this->notificationService->setTitle($notificationTitle);
                $this->notificationService->setIcon('red');
                $this->notificationService->setPriority(2);
                $this->notificationService
                    ->setNotificationConstEnum(NotificationEnum::NOTIFICATION_BUDGET_STATE_CHANGED);
                $this->notificationService->setBroadcastMessage($broadcastMessage);
                $this->notificationService->setBudgetData($budgetData);
                $this->notificationService->setProjectId($project->id);
                $this->notificationService->setNotificationTo($requestedUser);
                $this->notificationService->setDescription($notificationDescription);
                $this->notificationService->createNotification();
            }
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
            $requestedUser = User::find($verifiedRequest->requested);
            $notificationTitle = __(
                'notification.project.budget.verify_removed',
                [],
                $requestedUser?->language ?? 'de'
            );
            $this->removeSubPositionCellVerifiedValue($subPosition);
            $project = $mainPosition->table()->first()->project()->first();
            $budgetData->position_id = $mainPosition->id;
            $broadcastMessage = [
                'id' => Str::uuid()->toString(),
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

            if ($requestedUser !== null) {
                $this->notificationService->setTitle($notificationTitle);
                $this->notificationService->setIcon('red');
                $this->notificationService->setPriority(2);
                $this->notificationService
                    ->setNotificationConstEnum(NotificationEnum::NOTIFICATION_BUDGET_STATE_CHANGED);
                $this->notificationService->setBroadcastMessage($broadcastMessage);
                $this->notificationService->setBudgetData($budgetData);
                $this->notificationService->setProjectId($project->id);
                $this->notificationService->setNotificationTo($requestedUser);
                $this->notificationService->setDescription($notificationDescription);
                $this->notificationService->createNotification();
            }
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

        broadcast(new UpdateBudget($mainPosition->table->project_id));
    }

    public function verifiedRequestSubPosition(Request $request): void
    {
        $subPosition = SubPosition::find($request->id);
        $mainPosition = $subPosition->mainPosition()->first();
        $project = $mainPosition->table()->first()->project()->first();
        if ($request->giveBudgetAccess) {
            $project->users()->updateExistingPivot($request->user, ['access_budget' => true]);
            $user = User::find($request->user);
            if ($user !== null) {
                // Notification
                $notificationTitle = __(
                    'notification.project.budget.add',
                    [],
                    $user->language
                );
                $project = $mainPosition->table()->first()->project()->first();
                $broadcastMessage = [
                    'id' => Str::uuid()->toString(),
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
        }
        $subPosition->update(['is_verified' => BudgetTypeEnum::BUDGET_VERIFIED_TYPE_REQUESTED]);
        $notificationTitle = __(
            'notification.project.budget.new_verify_request',
            [],
            User::find($request->user)?->language ?? 'de'
        );
        $budgetData = new stdClass();
        $budgetData->position_id = $subPosition->id;
        $budgetData->requested_by = Auth::id();
        $budgetData->changeType = BudgetTypeEnum::BUDGET_VERIFICATION_REQUEST;
        $broadcastMessage = [
            'id' => Str::uuid()->toString(),
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
        $requestedUser = User::find($request->user);
        if ($requestedUser !== null) {
            $this->notificationService->setTitle($notificationTitle);
            $this->notificationService->setIcon('blue');
            $this->notificationService->setPriority(1);
            $this->notificationService->setNotificationConstEnum(NotificationEnum::NOTIFICATION_BUDGET_STATE_CHANGED);
            $this->notificationService->setPositionVerifyRequestType('sub');
            $this->notificationService->setPositionVerifyRequestId($subPosition->getAttribute('id'));
            $this->notificationService->setBroadcastMessage($broadcastMessage);
            $this->notificationService->setNotificationTo($requestedUser);
            $this->notificationService->setButtons(['calculation_check', 'delete_request']);
            $this->notificationService->setBudgetData($budgetData);
            $this->notificationService->setProjectId($project->id);
            $this->notificationService->setDescription($notificationDescription);
            $this->notificationService->createNotification();
        }

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

        broadcast(new UpdateBudget($mainPosition->table->project_id));
    }

    public function verifiedSubPosition(Request $request): void
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

        broadcast(new UpdateBudget($subPosition->mainPosition->table->project_id));
    }

    public function fixSubPosition(Request $request): void
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
                'id' => Str::uuid()->toString(),
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

        broadcast(new UpdateBudget($subPosition->mainPosition->table->project_id));
    }

    public function unfixSubPosition(Request $request): void
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
                'id' => Str::uuid()->toString(),
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

        broadcast(new UpdateBudget($subPosition->mainPosition->table->project_id));
    }

    public function fixMainPosition(Request $request): void
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

        broadcast(new UpdateBudget($mainPosition->table->project_id));
    }

    public function unfixMainPosition(Request $request): void
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

        broadcast(new UpdateBudget($mainPosition->table->project_id));
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
    ): void {
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

        broadcast(new UpdateBudget($project->id));
    }

    public function verifiedMainPosition(Request $request): void
    {
        $mainPosition = MainPosition::find($request->mainPositionId);
        abort_unless((bool) $mainPosition, 404);

        $this->setMainPositionCellVerifiedValue($mainPosition);
        $mainPosition->update(['is_verified' => 'BUDGET_VERIFIED_TYPE_CLOSED']);
        $verifiedRequest = $mainPosition->verified()->first();

        if ($verifiedRequest) {
            DatabaseNotification::query()
                ->whereJsonContains("data->budgetData->position_id", $mainPosition->id)
                ->whereJsonContains("data->budgetData->requested_by", $verifiedRequest->requested)
                ->whereJsonContains("data->budgetData->changeType", BudgetTypeEnum::BUDGET_VERIFICATION_REQUEST)
                ->delete();
        }

        $this->changeService->saveFromBuilder(
            $this->changeService
                ->createBuilder()
                ->setType('budget')
                ->setModelClass(Project::class)
                ->setModelId($request->project_id)
                ->setTranslationKey('Main position verified')
                ->setTranslationKeyPlaceholderValues([$mainPosition->name])
        );

        broadcast(new UpdateBudget($mainPosition->table->project_id));
    }

    private function setSubPositionCellVerifiedValue(SubPosition $subPosition): void
    {
        $this->bulkSetVerifiedValueForRows(
            $subPosition->subPositionRows()->select('id'),
            $subPosition->mainPosition->table->project_id,
            copyFromValue: true
        );
    }

    private function removeSubPositionCellVerifiedValue(SubPosition $subPosition): void
    {
        $this->bulkSetVerifiedValueForRows(
            $subPosition->subPositionRows()->select('id'),
            $subPosition->mainPosition->table->project_id,
            copyFromValue: false
        );
    }

    private function setMainPositionCellVerifiedValue(MainPosition $mainPosition): void
    {
        $this->bulkSetVerifiedValueForRows(
            SubPositionRow::query()
                ->whereIn('sub_position_id', $mainPosition->subPositions()->select('id'))
                ->select('id'),
            $mainPosition->table->project_id,
            copyFromValue: true
        );
    }

    private function removeMainPositionCellVerifiedValue(MainPosition $mainPosition): void
    {
        $this->bulkSetVerifiedValueForRows(
            SubPositionRow::query()
                ->whereIn('sub_position_id', $mainPosition->subPositions()->select('id'))
                ->select('id'),
            $mainPosition->table->project_id,
            copyFromValue: false
        );
    }

    /**
     * Setzt verified_value für alle Zellen der übergebenen Zeilen als EIN Bulk-UPDATE
     * statt einem Update pro Zelle. Bulk-Updates feuern keine Model-Events, daher
     * werden Cache-Invalidierung (BudgetUpdated) und Broadcast explizit ausgelöst.
     */
    private function bulkSetVerifiedValueForRows(
        mixed $rowIdsQuery,
        ?int $projectId,
        bool $copyFromValue
    ): void {
        ColumnCell::query()
            ->whereIn('sub_position_row_id', $rowIdsQuery)
            ->update(['verified_value' => $copyFromValue ? DB::raw('`value`') : null]);

        if ($projectId) {
            \Artwork\Modules\Budget\Events\BudgetUpdated::dispatch($projectId);
        }
        broadcast(new UpdateBudget($projectId));
    }

    public function updateCellSource(
        Request $request,
        MoneySourceThresholdReminderService $moneySourceThresholdReminderService,
        MoneySourceCalculationService $moneySourceCalculationService
    ) {
        $cell = ColumnCell::find($request->cell_id);
        abort_unless((bool) $cell, 404);

        $cell->update([
            'linked_type' => $request->linked_type,
            'linked_money_source_id' => $request->money_source_id
        ]);

        if ($request->money_source_id && ($moneySource = MoneySource::find($request->money_source_id))) {
            $moneySourceThresholdReminderService
                ->handleThresholdReminders(
                    $moneySource,
                    $moneySourceCalculationService,
                    $this->notificationService
                );
        }

        // Wenn AJAX-Request: JSON-Response
        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Cell source updated successfully'
            ]);
        }
    }

    public function updateColumnName(Request $request): void
    {
        $column = Column::find($request->column_id);
        abort_unless((bool) $column, 404);

        $column->update([
            'name' => $request->columnName
        ]);

        broadcast(new UpdateBudget($column->table->project_id));
    }
    public function updateTableName(Request $request): void
    {
        $table = Table::find($request->table_id);
        abort_unless((bool) $table, 404);

        $table->update([
            'name' => $request->table_name
        ]);


        broadcast(new UpdateBudget($table->project_id));
    }

    public function columnDelete(
        Column $column,
        ColumnService $columnService,
        ColumnRelevanceService $columnRelevanceService,
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
        $table = $column->table;

        // Eine Tabelle braucht immer mindestens eine Wertspalte: die letzte wird
        // nicht gelöscht, sondern geleert und bleibt budgetrelevant.
        if ($columnRelevanceService->isLastValueColumn($column)) {
            $column->cells()->update(['value' => '0', 'verified_value' => null]);
            $columnRelevanceService->assignExclusive($column);
            broadcast(new UpdateBudget($table->project_id));

            return Redirect::back()->with(
                'success',
                __('The last value column cannot be deleted and was emptied instead.')
            );
        }

        $hadRelevantFlag = $column->relevant_for_project_groups;

        // SoftDelete statt ForceDelete: ein Fehlklick soll keine Spalte samt
        // Zellen/Kommentaren/Sage-Zuordnungen unwiederbringlich vernichten.
        $columnService->softDelete(
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

        if ($hadRelevantFlag) {
            $columnRelevanceService->ensureSingleRelevantColumn($table);
        }

        return Redirect::back();
    }

    public function getTrashedColumns(Table $table): JsonResponse
    {
        return response()->json(
            $table->columns()
                ->onlyTrashed()
                ->orderBy('deleted_at', 'desc')
                ->get(['id', 'name', 'subName', 'type', 'deleted_at'])
        );
    }

    public function columnRestore(
        Column $column,
        ColumnService $columnService,
        ColumnRelevanceService $columnRelevanceService,
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
        $table = $column->table()->withTrashed()->first();
        $tableHadRelevantColumn = $table->columns()->where('relevant_for_project_groups', true)->exists();

        $columnService->restore(
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

        // Eine wiederhergestellte Spalte darf der aktuellen budgetrelevanten
        // Spalte das Flag nicht wegnehmen.
        $column->refresh();
        if ($tableHadRelevantColumn && $column->relevant_for_project_groups) {
            $column->update(['relevant_for_project_groups' => false]);
        }
        $columnRelevanceService->ensureSingleRelevantColumn($table);
        $columnService->setColumnSubName($table->id);

        return Redirect::back();
    }

    public function updateMainPositionName(Request $request): void
    {
        $mainPosition = MainPosition::find($request->mainPosition_id);
        $mainPosition->update([
            'name' => $request->mainPositionName
        ]);

        broadcast(new UpdateBudget($mainPosition->table->project_id));
    }

    public function updateSubPositionName(Request $request): void
    {
        $subPosition = subPosition::find($request->subPosition_id);
        $subPosition->update([
            'name' => $request->subPositionName
        ]);

        broadcast(new UpdateBudget($subPosition->mainPosition->table->project_id));
    }

    private function setColumnSubName(int $table_id): void
    {
        // Einzige Implementierung lebt im ColumnService (auch Sage100Service nutzt sie).
        app(ColumnService::class)->setColumnSubName($table_id);
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




    public function addColumn(Request $request, ColumnRelevanceService $columnRelevanceService): void
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

            // Die neueste Wertspalte gilt als aktueller Planungsstand und wird budgetrelevant.
            $columnRelevanceService->assignExclusive($column);
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


        broadcast(new UpdateBudget($table->project_id));
    }

    public function updateCellValue(
        Request $request,
        MoneySourceThresholdReminderService $moneySourceThresholdReminderService,
        MoneySourceCalculationService $moneySourceCalculationService,
        ColumnCellService $columnCellService
    ): \Illuminate\Http\JsonResponse {
        $column = Column::find($request->column_id);
        abort_unless((bool) $column, 404);

        $project = $column->table->project;
        $cell = ColumnCell::where('column_id', $request->column_id)
            ->where('sub_position_row_id', $request->sub_position_row_id)
            ->first();

        if ($cell === null) {
            return response()->json(['message' => 'Cell not found.'], 404);
        }

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
        $columnCellService->recalculateAutomaticColumns($request->sub_position_row_id);

        if ($linkedMoneySourceId = $cell->linked_money_source_id) {
            // Threshold-Prüfung inkl. Notifications erst nach der Response -
            // sie darf die Zell-Eingabe nicht ausbremsen.
            $notificationService = $this->notificationService;
            dispatch(static function () use (
                $linkedMoneySourceId,
                $moneySourceThresholdReminderService,
                $moneySourceCalculationService,
                $notificationService
            ): void {
                if ($moneySource = MoneySource::find($linkedMoneySourceId)) {
                    $moneySourceThresholdReminderService->handleThresholdReminders(
                        $moneySource,
                        $moneySourceCalculationService,
                        $notificationService
                    );
                }
            })->afterResponse();
        }

        broadcast(new UpdateBudget($project->id))->toOthers();

        // Schlanker Patch statt Client-Full-Refetch: frische Zellen der Zeile
        // + neu berechnete Summen. Waermt zugleich den Budget-Cache auf.
        return response()->json(
            $this->budgetService->buildCellUpdatePatchForRow($project, (int) $request->sub_position_row_id)
        );
    }

    public function changeColumnColor(Request $request): void
    {
        $column = Column::find($request->columnId);
        abort_unless((bool) $column, 404);

        $column->update(['color' => $request->color]);

        broadcast(new UpdateBudget($column->table->project_id));
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

        SubPositionRow::query()
            ->where('sub_position_id', $request->sub_position_id)
            ->where('order', '>', $request->positionBefore)
            ->increment('order');

        /** @var SubPositionRow $subPositionRow */
        $subPositionRow = $subPosition->subPositionRows()->create([
            'commented' => false,
            'position' => $request->positionBefore + 1,
            'order' => $request->positionBefore + 1,
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

        broadcast(new UpdateBudget($table->project_id))->toOthers();
    }

    public function reorderSubPositionRows(Request $request, BudgetCacheService $budgetCacheService): RedirectResponse
    {
        $validated = $request->validate([
            'updates' => ['required', 'array', 'min:1'],
            'updates.*.sub_position_id' => ['required', 'integer', 'exists:sub_positions,id'],
            'updates.*.row_ids' => ['required', 'array'],
            'updates.*.row_ids.*' => ['required', 'integer', 'exists:sub_position_rows,id'],
        ]);

        $updates = $validated['updates'];
        $allRowIds = collect($updates)->pluck('row_ids')->flatten()->all();

        if (count($allRowIds) !== count(array_unique($allRowIds))) {
            throw ValidationException::withMessages([
                'updates' => ['Row-IDs dürfen nicht mehrfach vorkommen.'],
            ]);
        }

        DB::transaction(function () use ($updates): void {
            foreach ($updates as $update) {
                $subPositionId = (int)$update['sub_position_id'];
                $rowIds = $update['row_ids'];

                foreach ($rowIds as $index => $rowId) {
                    SubPositionRow::query()
                        ->whereKey($rowId)
                        ->update([
                            'sub_position_id' => $subPositionId,
                            'order' => $index + 1,
                            // keep legacy ordering column in sync
                            'position' => $index + 1,
                        ]);
                }
            }
        });

        // Cache invalidieren
        $firstSubPositionId = $updates[0]['sub_position_id'] ?? null;
        if ($firstSubPositionId) {
            $subPosition = SubPosition::find($firstSubPositionId);
            if ($subPosition) {
                $mainPosition = MainPosition::find($subPosition->main_position_id);
                if ($mainPosition) {
                    $table = Table::find($mainPosition->table_id);
                    if ($table && $table->project_id) {
                        $project = Project::find($table->project_id);
                        if ($project) {
                            $budgetCacheService->forgetForProjectGroup($project);
                        }
                    }
                }
            }
        }

        // Inertia expects a redirect (or a valid Inertia response). Without this,
        // the client will not treat the request as successful.
        return Redirect::back();
    }

    public function reorderMainPositions(Request $request, BudgetCacheService $budgetCacheService): RedirectResponse
    {
        $validated = $request->validate([
            'table_id' => ['required', 'integer', 'exists:tables,id'],
            'type' => ['required', 'string'],
            'main_position_ids' => ['required', 'array', 'min:1'],
            'main_position_ids.*' => ['required', 'integer', 'exists:main_positions,id'],
        ]);

        DB::transaction(function () use ($validated): void {
            foreach ($validated['main_position_ids'] as $index => $mainPositionId) {
                MainPosition::query()
                    ->whereKey($mainPositionId)
                    ->update(['position' => $index + 1]);
            }
        });

        $table = Table::find($validated['table_id']);
        if ($table && $table->project_id) {
            $project = Project::find($table->project_id);
            if ($project) {
                $budgetCacheService->forgetForProjectGroup($project);
            }
        }

        return Redirect::back();
    }

    public function reorderSubPositions(Request $request, BudgetCacheService $budgetCacheService): RedirectResponse
    {
        $validated = $request->validate([
            'main_position_id' => ['required', 'integer', 'exists:main_positions,id'],
            'sub_position_ids' => ['required', 'array', 'min:1'],
            'sub_position_ids.*' => ['required', 'integer', 'exists:sub_positions,id'],
        ]);

        DB::transaction(function () use ($validated): void {
            foreach ($validated['sub_position_ids'] as $index => $subPositionId) {
                SubPosition::query()
                    ->whereKey($subPositionId)
                    ->update([
                        'main_position_id' => $validated['main_position_id'],
                        'position' => $index + 1,
                    ]);
            }
        });

        $mainPosition = MainPosition::find($validated['main_position_id']);
        if ($mainPosition) {
            $table = Table::find($mainPosition->table_id);
            if ($table && $table->project_id) {
                $project = Project::find($table->project_id);
                if ($project) {
                    $budgetCacheService->forgetForProjectGroup($project);
                }
            }
        }

        return Redirect::back();
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
        broadcast(new UpdateBudget($table->project_id));
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
        broadcast(new UpdateBudget($table->project_id));
    }

    private function addSubPositionRowsWithColumns(SubPosition $subPosition, Collection $columns): void
    {
        /** @var SubPositionRow $subPositionRow */
        $subPositionRow = $subPosition->subPositionRows()->create([
            'commented' => false,
            'position' => 1,
            'order' => 1,
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

        broadcast(new UpdateBudget($subPosition->mainPosition->table->project_id));
    }

    public function updateCellCalculation(Request $request)
    {
        // Hole cell_id aus Request (wird jetzt immer mitgesendet)
        $cellId = $request->input('cell_id');
        $calculations = $request->input('calculations', []);
        $sentCalculationIds = []; // Sammle IDs der gesendeten Kalkulationen

        // Verarbeite Kalkulationen nur wenn welche vorhanden sind
        if (is_array($calculations) && count($calculations) > 0) {
            foreach ($calculations as $calculation) {
                // Prüfe ob Kalkulation eine ID hat (existierend) oder neu ist
                if (isset($calculation['id']) && !empty($calculation['id']) && is_numeric($calculation['id'])) {
                    // Existierende Kalkulation aktualisieren
                    $cellCalculation = CellCalculation::find($calculation['id']);
                    if ($cellCalculation) {
                        $cellCalculation->update([
                            'name' => $calculation['name'] ?? '',
                            'value' => $calculation['value'] ?? 0,
                            'description' => $calculation['description'] ?? '',
                            'position' => $calculation['position'] ?? 0
                        ]);
                        $sentCalculationIds[] = $calculation['id']; // Merke diese ID
                    }
                } else {
                    // Neue Kalkulation erstellen (hat keine ID oder tempId)
                    if ($cellId) {
                        $cellCalculation = CellCalculation::create([
                            'cell_id' => $cellId,
                            'name' => $calculation['name'] ?? '',
                            'value' => $calculation['value'] ?? 0,
                            'description' => $calculation['description'] ?? '',
                            'position' => $calculation['position'] ?? 0
                        ]);
                        $sentCalculationIds[] = $cellCalculation->id; // Merke neue ID
                    }
                }
            }
        }

        // Lösche alle Kalkulationen der Cell, die NICHT im Request enthalten sind
        if ($cellId) {
            if (count($sentCalculationIds) > 0) {
                // Lösche nur die nicht gesendeten Kalkulationen
                CellCalculation::where('cell_id', $cellId)
                    ->whereNotIn('id', $sentCalculationIds)
                    ->delete();
            } else {
                // Leeres Array: Lösche ALLE Kalkulationen für diese Cell
                CellCalculation::where('cell_id', $cellId)->delete();
            }

            // Aktualisiere den Cell-Value auf die Summe aller Kalkulationen
            $cell = ColumnCell::find($cellId);
            if ($cell) {
                $cell->update(['value' => $cell->calculations()->sum('value')]);
            }

            // Wenn AJAX-Request: JSON-Response
            if ($request->wantsJson() || $request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Calculations saved successfully',
                    'cell_value' => $cell ? $cell->value : null
                ]);
            }
        }

        return Redirect::back();
    }

    public function addCalculation(ColumnCell $cell, Request $request)
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

        // Wenn es ein AJAX-Request ist, gib JSON zurück statt Inertia-Reload
        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'calculation' => $newCalculation,
                'calculations' => $cell->fresh()->calculations()->orderBy('position', 'asc')->get()
            ]);
        }

        return Redirect::back();
    }

    public function lockColumn(Request $request): void
    {
        $column = Column::find($request->columnId);
        abort_unless((bool) $column, 404);

        $column->update(['is_locked' => true, 'locked_by' => Auth::id()]);
        broadcast(new UpdateBudget($column->table->project_id));
    }

    public function unlockColumn(Request $request): void
    {
        $column = Column::find($request->columnId);
        abort_unless((bool) $column, 404);

        $column->update(['is_locked' => false, 'locked_by' => null]);
        broadcast(new UpdateBudget($column->table->project_id));
    }

    public function updateProjectState(Request $request, Project $project): void
    {
        // Hole alte State-ID bevor wir updaten
        $oldStateId = optional($project->state);



        // Aktualisiere nur, wenn wirklich eine Änderung vorliegt
        $newStateId = $request->input('state');

        // Prüfe ob sich der State geändert hat (auch wenn auf null gesetzt wird)
        if ($project->state !== $newStateId && ($newStateId === null || (int) $project->state !== (int) $newStateId)) {
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

    private function inertiaProjectError(Request $request, int $status, string $title, string $message)
    {
        $projectsIndexHref = Route::has('projects.index') ? route('projects.index') : url('/projects');
        $homeHref = Route::has('dashboard') ? route('dashboard') : url('/');

        return inertia('Errors/ProjectError', [
            'status' => $status,
            'title' => $title,
            'message' => $message,
            'projectsIndexHref' => $projectsIndexHref,
            'homeHref' => $homeHref,
        ])->toResponse($request)->setStatusCode($status);
    }


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
        EventPropertyService $eventPropertyService,
        ShiftTimePresetService $shiftTimePresetService
    ) {


        if (method_exists($project, 'trashed') ? $project->trashed() : (bool) $project->deleted_at) {
            return $this->inertiaProjectError(
                $request,
                410,
                'Project has been deleted',
                'This project has been deleted and is no longer available.'
            );
        }

        $headerObject = new stdClass();
        $headerObject->project = $project->append('first_and_last_event_date');
        $headerObject->project->cost_center = $project->costCenter;

        $loadedProjectInformation = [];

        /** @var User|null $authUser */
        $authUser = $userService->getAuthUser();

        if ($authUser?->last_project_id !== $project->id) {
            $authUser?->update(['last_project_id' => $project->id]);
        }

        $projectTab->load([
            'visibleUsers:id,first_name,last_name',
            'visibleDepartments:id,name,svg_name',

            // Hauptkomponenten inkl. ProjectValue
            'components.component.projectValue' => function ($query) use ($project): void {
                $query->where('project_id', $project->id);
            },
            'components' => function ($query): void {
                $query->orderBy('order');
            },

            // Berechtigungsdaten der Hauptkomponenten (nur ID und can_write vom Pivot)
            'components.component.users' => function ($query): void {
                $query->select('users.id', 'users.first_name', 'users.last_name')->withPivot('can_write');
            },
            'components.component.departments' => function ($query): void {
                $query->select('departments.id', 'departments.name', 'departments.svg_name')->withPivot('can_write');
            },
            'components.component.departments.users' => function ($query): void {
                $query->select('users.id', 'users.first_name', 'users.last_name');
            },

            // Sidebar-Komponenten inkl. ProjectValue
            'sidebarTabs.componentsInSidebar.component.projectValue' => function ($query) use ($project): void {
                $query->where('project_id', $project->id);
            },
            'sidebarTabs.componentsInSidebar.component.users' => function ($query): void {
                $query->select('users.id', 'users.first_name', 'users.last_name')->withPivot('can_write');
            },
            'sidebarTabs.componentsInSidebar.component.departments' => function ($query): void {
                $query->select('departments.id', 'departments.name', 'departments.svg_name')->withPivot('can_write');
            },
            'sidebarTabs.componentsInSidebar.component.departments.users' => function ($query): void {
                $query->select('users.id', 'users.first_name', 'users.last_name');
            },

            // Disclosure-Komponenten (falls angezeigt)
            'components.disclosureComponents.component.projectValue' => function ($query) use ($project): void {
                $query->where('project_id', $project->id);
            },
            'components.disclosureComponents.component.users' => function ($query): void {
                $query->select('users.id', 'users.first_name', 'users.last_name')->withPivot('can_write');
            },
            'components.disclosureComponents.component.departments' => function ($query): void {
                $query->select('departments.id', 'departments.name', 'departments.svg_name')->withPivot('can_write');
            },
            'components.disclosureComponents.component.departments.users' => function ($query): void {
                $query->select('users.id', 'users.first_name', 'users.last_name');
            },
        ]);

        if (!$projectTab->visibleForUser($authUser)) {
            return $this->inertiaProjectError(
                $request,
                410,
                'Project Tab not accessible',
                'You do not have permission to access this project tab.'
            );
        }

        $this->inventoryUserFilterShareService->getFilterDataForUser($authUser);

        $firstEvent = $this->projectService->getFirstEventInProject($project);
        $lastEvent  = $this->projectService->getLatestEndingEventInProject($project);

        $projectTabComponents = $projectTab
            ->components()
            ->with(['component'])
            ->get()
            ->concat(
                $projectTab->sidebarTabs->flatMap->componentsInSidebar->unique('id')
            );

        // Erkenne, welche Komponententypen im aktuellen Tab vorhanden sind
        $componentTypes = $projectTabComponents
            ->pluck('component.type')
            ->unique()
            ->toArray();

        // Load project relations only if the currently rendered tab needs them
        $hasProjectBasicDataDisplayComponent = in_array(
            ProjectTabComponentEnum::PROJECT_BASIC_DATA_DISPLAY->value,
            $componentTypes,
            true
        );
        if ($hasProjectBasicDataDisplayComponent) {
            $project->loadMissing([
                'costCenter',
                'status',
                'categories',
                'genres',
                'sectors',
            ]);
            $headerObject->project->cost_center = $project->costCenter;
        }

        $hasShiftTab    = in_array(ProjectTabComponentEnum::SHIFT_TAB->value, $componentTypes, true);
        $hasCalendarTab = in_array(ProjectTabComponentEnum::CALENDAR->value, $componentTypes, true);
        $hasBudgetTab   = in_array(ProjectTabComponentEnum::BUDGET->value, $componentTypes, true);

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

        // Schichten können auch außerhalb des Event-Zeitraums liegen (z.B. Auf-/Abbau);
        // der Schichttab richtet seinen Zeitraum am Min/Max über Events UND Schichten aus.
        $headerObject->firstShiftInProject = $project->shifts()->min('start_date');
        $headerObject->lastShiftInProject  = $project->shifts()->max('end_date');

        $headerObject->roomsWithAudience = Room::withAudience($project->id)->pluck('name', 'id');
        $headerObject->eventTypes        = $this->eventTypeService->getAll();
        $headerObject->eventStatuses     = app(EventSettings::class)->enable_status
            ? EventStatus::orderBy('order')->get()
            : [];
        $headerObject->event_properties  = $eventPropertyService->getAll();
        $headerObject->states            = $this->projectStateService->getAll();

        $headerObject->projectGroups     = $project->groups;

        // Append first_and_last_event_date to groups so the frontend can show project periods
        $project->loadMissing('groups');
        $project->groups->each->append('first_and_last_event_date');

        $hasGroupComponent = in_array('ProjectGroupComponent', $componentTypes, true);
        if ($hasGroupComponent) {
            $headerObject->groupProjects = Project::where('is_group', 1)->get();
        } else {
            $headerObject->groupProjects = collect();
        }

        $headerObject->projectsOfGroup = $project->projectsOfGroup()->get()->each->append('first_and_last_event_date');

        $hasAttributesComponent = in_array('ProjectAttributesComponent', $componentTypes, true);
        $needsProjectAttributesData = $hasAttributesComponent || (bool) ($projectCreateSettings->attributes ?? false);
        if ($needsProjectAttributesData) {
            $headerObject->categories = $this->categoryService->getAll();
            $headerObject->genres     = $this->genreService->getAll();
            $headerObject->sectors    = $this->sectorService->getAll();
        } else {
            $headerObject->categories = [];
            $headerObject->genres     = [];
            $headerObject->sectors    = [];
        }

        $headerObject->projectCategories = $project->categories;
        $headerObject->projectGenres     = $project->genres;
        $headerObject->projectSectors    = $project->sectors;

        $headerObject->rooms = Room::select('id', 'name')->whereNull('deleted_at')->get();

        $headerObject->projectState = $project->state;

        $project->load('status');
        $headerObject->project->state = $project->status;

        $tabInformation = [];
        ProjectTab::query()
            ->visibleForUser($authUser)
            ->orderBy('order')
            ->get(['id', 'name'])
            ->each(function ($tab) use (&$tabInformation): void {
                $tabInformation[] = ['id' => $tab->id, 'name' => $tab->name];
            });
        $headerObject->tabs = $tabInformation;

        $headerObject->currentTabId = $projectTab->id;
        $headerObject->currentGroup = $groupOutput;

        $headerObject->projectManagerIds = $project->managerUsers()->pluck('user_id');
        $headerObject->projectWriteIds   = $project->writeUsers()->pluck('user_id');
        $headerObject->projectDeleteIds  = $project->delete_permission_users()->pluck('user_id');

        $headerObject->projectCategoryIds = $project->categories()->pluck('category_id');
        $headerObject->projectGenreIds    = $project->genres()->pluck('genre_id');
        $headerObject->projectSectorIds   = $project->sectors()->pluck('sector_id');

        $headerObject->project->project_managers = $project->managerUsers;

        $latestActivity = $project->activities()->latest()->first();
        $latestChange  = [];
        if ($latestActivity !== null) {
            $properties = $latestActivity->properties;
            $latestChange = [[
                'changes'    => $properties instanceof \Illuminate\Support\Collection
                    ? $properties->all()
                    : ($properties ?? null),
                'created_at' => $latestActivity->created_at->diffInHours() < 24
                    ? $latestActivity->created_at->diffForHumans()
                    : $latestActivity->created_at->format('d.m.Y, H:i'),
                'changer'    => $latestActivity->causer()
                    ->without(['roles', 'departments', 'calendar_settings', 'calendarAbo', 'shiftCalendarAbo'])
                    ->first(),
            ]];
        }
        $headerObject->project_history = $latestChange;

        /** @var User $user */
        $user = $this->authManager->user();


        $firstVisibleTabId = ProjectTab::query()
            ->visibleForUser($authUser)
            ->orderBy('order')
            ->value('id');

        $firstVisibleCalendarTabId = ProjectTab::query()
            ->visibleForUser($authUser)
            ->byComponentsComponentType(ProjectTabComponentEnum::CALENDAR->value)
            ->orderBy('order')
            ->value('id') ?? $firstVisibleTabId;

        $firstVisibleBudgetTabId = ProjectTab::query()
            ->visibleForUser($authUser)
            ->byComponentsComponentType(ProjectTabComponentEnum::BUDGET->value)
            ->orderBy('order')
            ->value('id') ?? $firstVisibleTabId;

        $baseData = [
            'currentTab'                   => $projectTab,
            'headerObject'                 => $headerObject,
            'loadedProjectInformation'     => $loadedProjectInformation,
            'first_project_tab_id'         => $firstVisibleTabId,
            'first_project_calendar_tab_id'=> $firstVisibleCalendarTabId,
            'first_project_budget_tab_id'  => $firstVisibleBudgetTabId,
            'createSettings'               => app(ProjectCreateSettings::class),
            'printLayouts'                 => $this->projectPrintLayoutService->getAll(),
            'project'                      => $headerObject->project,
            'eventTypes'                   => $this->eventTypeService->getAll(),
            'eventStatuses'                => app(EventSettings::class)->enable_status
                ? EventStatus::orderBy('order')->get()
                : [],
            'event_properties'             => $eventPropertyService->getAll(),
            'projectId'                    => $project->id,
        ];

        $tabSpecificData = [];

        if ($hasShiftTab) {
            $this->loadShiftTabData($headerObject, $project);

            $userCalendarFilter = $user->userFilters()->firstOrCreate(
                ['filter_type' => UserFilterTypes::PROJECT_SHIFT_FILTER->value],
                [
                    'start_date' => null,
                    'end_date' => null,
                    'event_type_ids' => null,
                    'room_ids' => null,
                    'area_ids' => null,
                    'room_attribute_ids' => null,
                    'room_category_ids' => null,
                    'event_property_ids' => null,
                    'craft_ids' => null,
                ]
            );

            $userCalendarSettings = $user->getAttribute('calendar_settings');

            $startDate = $firstEvent?->getAttribute('start_time')?->copy()?->startOfDay() ?? Carbon::now()->startOfDay();
            $endDate   = $lastEvent?->getAttribute('end_time')?->copy()?->endOfDay() ?? $startDate->copy()->endOfDay();

            /** @var CalendarDataService $calendarDataService */
            $calendarDataService = app(CalendarDataService::class);
            $rooms = $calendarDataService->getFilteredRooms(
                $userCalendarFilter,
                $userCalendarSettings,
                $startDate,
                $endDate,
                true
            );

            $roomDTOs = $rooms->map(fn($room) => new RoomDTO(
                id: $room->id,
                name: $room->name,
                has_events: $room->events_count > 0,
                admins: $room->admins->pluck('id')->toArray(),
                everyone_can_book: $room->everyone_can_book,
                requestable_by: $room->requestableBy->pluck('id')->toArray(),
            ));

            $dateValue = [
                $startDate ? $startDate->format('Y-m-d') : null,
                $endDate ? $endDate->format('Y-m-d') : null,
            ];

            $history = app(ShiftCalendarService::class)->getEventShiftsHistoryChanges();

            $tabSpecificData = array_merge($tabSpecificData, $this->getShiftTabInertiaData(
                $project,
                $craftService,
                $shiftQualificationService,
                $filterService,
                $eventPropertyService,
                $shiftTimePresetService,
                $user,
                $userService,
                $dateValue,
                $history
            ));

            $tabSpecificData['rooms'] = $roomDTOs;
            $tabSpecificData['user_filters'] = $userCalendarFilter;
        }

        if ($hasCalendarTab) {
            $tabSpecificData = array_merge($tabSpecificData, $this->getCalendarTabInertiaData());
        }

        if ($hasBudgetTab) {
            $tabSpecificData = array_merge($tabSpecificData, $this->getBudgetTabInertiaData());
        }

        // Load data for PROJECT_CONTRACTS_DOCUMENTS component
        $hasContractsDocumentsComponent = in_array(
            ProjectTabComponentEnum::PROJECT_CONTRACTS_DOCUMENTS->value,
            $componentTypes,
            true
        );

        if ($hasContractsDocumentsComponent) {
            $tabSpecificData = array_merge($tabSpecificData, $this->getContractsDocumentsTabInertiaData(
                $project,
                $authUser,
                $contractTypeService,
                $companyTypeService,
                $currencyService
            ));
        }

        return inertia('Projects/Tab/TabContent', array_merge($baseData, $tabSpecificData));
    }


    public function history(Project $project): JsonResponse
    {
        $activities = $project->activities()->latest()->get();
        $history = $activities->map(static function ($activity) {
            $properties = $activity->properties;

            return [
                'changes'    => $properties instanceof \Illuminate\Support\Collection
                    ? $properties->all()
                    : ($properties ?? null),
                'created_at' => $activity->created_at->diffInHours() < 24
                    ? $activity->created_at->diffForHumans()
                    : $activity->created_at->format('d.m.Y, H:i'),
                'changer'    => $activity->causer()
                    ->without(['roles', 'departments', 'calendar_settings', 'calendarAbo', 'shiftCalendarAbo'])
                    ->first(),
            ];
        })->all();

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
        // Step 4: Nur essentielle Felder für Freelancers und ServiceProviders laden
        $headerObject->project->freelancers                = Freelancer::select('id', 'first_name', 'last_name')->get();
        $headerObject->project->serviceProviders           = ServiceProvider::select('id', 'provider_name')->get();
    }

    /**
     * Liefert ShiftTab-spezifische Daten als Array für Inertia.
     *
     * @return array<string,mixed>
     */
    private function getShiftTabInertiaData(
        Project $project,
        CraftService $craftService,
        ShiftQualificationService $shiftQualificationService,
        FilterService $filterService,
        EventPropertyService $eventPropertyService,
        ShiftTimePresetService $shiftTimePresetService,
        User $user,
        UserService $userService,
        array $dateValue,
        array $history
    ): array {
        return [
            // Crafts mit allen notwendigen Relationen für ShiftPlanDailyView
            // Benötigt: users, freelancers, serviceProviders mit shift_qualifications
            'crafts' => Craft::with([
                'users',
                'freelancers',
                'serviceProviders',
                'managingUsers',
                'managingFreelancers',
                'managingServiceProviders',
                'qualifications'
            ])->without(['craftShiftPlaner', 'craftInventoryPlaner'])->get(),
            // Step 2: Tags/TagGroups entfernt - werden nicht im ShiftTab verwendet
            // Step 3: History entfernt - wird per API geladen (/projects/{project}/history)
            'personalFilters' => $filterService->getPersonalFilter($user, UserFilterTypes::PROJECT_SHIFT_FILTER->value),
            'filterOptions' => $filterService->getCalendarFilterDefinitions(),
            'dateValue' => $dateValue,
            'shiftQualifications' => $shiftQualificationService->getAllOrderedByCreationDateAscending(),
            'firstProjectShiftTabId' => $this->projectTabService
                ->getFirstProjectTabWithTypeIdOrFirstProjectTabId(ProjectTabComponentEnum::SHIFT_TAB),
            'currentUserCrafts' => $this->getCurrentUserCraftsForShiftTab($user, $craftService),
            'shiftTimePresets' => $shiftTimePresetService->getAll(),
            'globalQualifications' => app(GlobalQualificationService::class)->getAll(),
            'shiftGroups' => app(ShiftGroupService::class)->getAllShiftGroups(),
        ];
    }

    /**
     * Liefert CalendarTab-spezifische Daten als Array für Inertia (falls benötigt).
     * Aktuell werden Calendar-Daten hauptsächlich on-demand geladen,
     * aber hier können gemeinsame Basis-Daten bereitgestellt werden.
     *
     * @return array<string,mixed>
     */
    private function getCalendarTabInertiaData(): array
    {
        // CalendarTab lädt die meisten Daten bereits per AJAX (siehe CalendarTab.vue)
        // Hier nur Basis-Daten, falls direkt benötigt
        return [];
    }

    /**
     * Liefert BudgetTab-spezifische Daten als Array für Inertia (falls benötigt).
     *
     * @return array<string,mixed>
     */
    private function getBudgetTabInertiaData(): array
    {
        // BudgetTab-spezifische Daten können hier hinzugefügt werden
        // Aktuell werden diese bei Bedarf geladen
        return [];
    }

    /**
     * Liefert Contracts & Documents Tab-spezifische Daten als Array für Inertia.
     *
     * @return array<string,mixed>
     */
    private function getContractsDocumentsTabInertiaData(
        Project $project,
        ?User $authUser,
        ContractTypeService $contractTypeService,
        CompanyTypeService $companyTypeService,
        CurrencyService $currencyService
    ): array {
        $userId = $authUser?->id;

        // Load contracts for this project with necessary relations
        $contracts = $project->contracts()
            ->with(['accessingUsers', 'accessingDepartments', 'contract_type', 'company_type', 'currency'])
            ->get()
            ->map(function ($contract) use ($project) {
                return [
                    'id' => $contract->id,
                    'name' => $contract->name,
                    'basename' => $contract->basename,
                    'partner' => $contract->contract_partner,
                    'accessibleUsers' => $contract->accessingUsers->map(fn($user) => [
                        'id' => $user->id,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'profile_photo_url' => $user->profile_photo_url,
                    ]),
                    'accessibleDepartments' => $contract->accessingDepartments->map(fn($dept) => [
                        'id' => $dept->id,
                        'name' => $dept->name,
                        'svg_name' => $dept->svg_name,
                    ]),
                    'project' => [
                        'id' => $project->id,
                        'name' => $project->name,
                    ],
                    'contract_type' => $contract->contract_type,
                    'company_type' => $contract->company_type,
                    'currency' => $contract->currency,
                    'ksk_liable' => $contract->ksk_liable,
                    'ksk_amount' => $contract->ksk_amount,
                    'ksk_reason' => $contract->ksk_reason,
                    'resident_abroad' => $contract->resident_abroad,
                    'has_foreign_tax' => $contract->foreign_tax,
                    'foreign_tax_amount' => $contract->foreign_tax_amount,
                    'foreign_tax_reason' => $contract->foreign_tax_reason,
                    'reverse_charge_amount' => $contract->reverse_charge_amount,
                    'has_power_of_attorney' => $contract->has_power_of_attorney,
                    'is_freed' => $contract->is_freed,
                    'deadline_date' => $contract->deadline_date,
                    'amount' => $contract->amount,
                    'description' => $contract->description,
                    'currency_id' => $contract->currency_id,
                ];
            });

        // Load document requests for this project (created by user or assigned to user)
        $docRequestEagerLoad = ['requester', 'requested', 'project', 'contract', 'contractType', 'companyType', 'crmContact.contactType'];

        $createdRequests = \Artwork\Modules\DocumentRequest\Models\DocumentRequest::where('requester_id', $userId)
            ->where('project_id', $project->id)
            ->with($docRequestEagerLoad)
            ->get()
            ->map(fn($request) => $this->mapDocumentRequest($request));

        $assignedRequests = \Artwork\Modules\DocumentRequest\Models\DocumentRequest::where('requested_id', $userId)
            ->where('project_id', $project->id)
            ->with($docRequestEagerLoad)
            ->get()
            ->map(fn($request) => $this->mapDocumentRequest($request));

        return [
            'projectContracts' => $contracts,
            'projectCreatedRequests' => $createdRequests,
            'projectAssignedRequests' => $assignedRequests,
            'contractTypes' => $contractTypeService->getAll(),
            'companyTypes' => $companyTypeService->getAll(),
            'currencies' => $currencyService->getAll(),
            'crmContactTypes' => app(\Artwork\Modules\Crm\Services\CrmContactTypeService::class)->getActive(),
        ];
    }

    /**
     * Map a document request to an array for frontend.
     *
     * @return array<string,mixed>
     */
    private function mapDocumentRequest(\Artwork\Modules\DocumentRequest\Models\DocumentRequest $request): array
    {
        return [
            'id' => $request->id,
            'title' => $request->title,
            'description' => $request->description,
            'status' => $request->status,
            'deadline_date' => $request->deadline_date?->format('Y-m-d'),
            'contract_partner' => $request->contract_partner,
            'crm_contact_id' => $request->crm_contact_id,
            'crm_contact' => $request->crmContact ? [
                'id' => $request->crmContact->id,
                'display_name' => $request->crmContact->display_name,
                'profile_photo_url' => $request->crmContact->profile_photo_url,
                'contact_type' => $request->crmContact->contactType ? [
                    'id' => $request->crmContact->contactType->id,
                    'name' => $request->crmContact->contactType->name,
                ] : null,
            ] : null,
            'contract_value' => $request->contract_value,
            'ksk_liable' => $request->ksk_liable,
            'ksk_amount' => $request->ksk_amount,
            'ksk_reason' => $request->ksk_reason,
            'foreign_tax' => $request->foreign_tax,
            'foreign_tax_amount' => $request->foreign_tax_amount,
            'foreign_tax_reason' => $request->foreign_tax_reason,
            'reverse_charge_amount' => $request->reverse_charge_amount,
            'comment' => $request->comment,
            'contract_type' => $request->contractType,
            'company_type' => $request->companyType,
            'requester' => $request->requester ? [
                'id' => $request->requester->id,
                'first_name' => $request->requester->first_name,
                'last_name' => $request->requester->last_name,
                'profile_photo_url' => $request->requester->profile_photo_url,
            ] : null,
            'requested' => $request->requested ? [
                'id' => $request->requested->id,
                'first_name' => $request->requested->first_name,
                'last_name' => $request->requested->last_name,
                'profile_photo_url' => $request->requested->profile_photo_url,
            ] : null,
            'project' => $request->project ? [
                'id' => $request->project->id,
                'name' => $request->project->name,
            ] : null,
            'contract' => $request->contract ? [
                'id' => $request->contract->id,
                'name' => $request->contract->name,
            ] : null,
        ];
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

    /**
     * Get crafts that the current user is allowed to assign in shift planning.
     */
    private function getCurrentUserCraftsForShiftTab(User $user, CraftService $craftService): \Illuminate\Database\Eloquent\Collection
    {
        // If user is admin, return all crafts with qualifications
        if ($user->hasRole('artwork admin')) {
            return $craftService->getAll(['qualifications']);
        }

        // Get crafts that are assignable by all (not restricted)
        $assignableByAllCrafts = $craftService->getAssignableByAllCrafts();

        // Get crafts where user is explicitly allowed (restricted crafts via craft_users table)
        $userRestrictedCrafts = $user->crafts()->with(['qualifications'])->get();

        // Merge both collections and remove duplicates by craft id
        return $assignableByAllCrafts->merge($userRestrictedCrafts)->unique('id');
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
            'users' => User::select(['id', 'first_name', 'last_name', 'email', 'profile_photo_path'])->get(),
            'departments' => Department::select(['id', 'name'])->get()
        ]);
    }

    public function update(UpdateProjectRequest $request, Project $project): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $project);
        DB::table('project_groups')->where('project_id', '=', $project->id)->delete();
        if ($request->get('selectedGroup') !== null) {
            $group = Project::find($request->get('selectedGroup')['id'] ?? null);
            if ($group) {
                $group->projectsOfGroup()->syncWithoutDetaching($project->id);

                // Ensure the group's is_group flag is set to true
                if (!$group->is_group) {
                    $group->is_group = true;
                    $group->save();
                }
            }

            // Ensure the project has exactly one budget-relevant column
            $table = $project->table()->first();
            if ($table) {
                app(ColumnRelevanceService::class)->ensureSingleRelevantColumn($table);
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

        // Nur syncen, wenn das Feld explizit als Array mitkommt – andere Aufrufer von
        // projects.update würden sonst bestehende CRM-Verknüpfungen löschen.
        if (is_array($request->input('crm_artist_contact_ids'))) {
            $oldLinkedCrmContactIds = $project->crmContacts()->pluck('crm_contacts.id')->sort()->values()->all();
            $this->projectService->syncCrmArtistContacts($project, $request->input('crm_artist_contact_ids'));
            $newLinkedCrmContactIds = $project->crmContacts()->pluck('crm_contacts.id')->sort()->values()->all();

            if ($oldLinkedCrmContactIds !== $newLinkedCrmContactIds) {
                $this->changeService->saveFromBuilder(
                    $this->changeService
                        ->createBuilder()
                        ->setType('public_changes')
                        ->setModelClass(Project::class)
                        ->setModelId($project->id)
                        ->setTranslationKey('Project artists changed')
                );
                $this->setPublicChangesNotification($project->id);
            }
        }

        $this->projectService->syncManagementUsers($project, $request->assignedUsers ?? []);

        if ($request->boolean('isGroup')) {
            $project->update(['is_group' => true]);
            $project->projectsOfGroup()->sync($request->get('projects', []));

            foreach ($request->get('projects', []) as $projectId) {
                $childProject = Project::find($projectId);
                if ($childProject) {
                    $table = $childProject->table()->first();
                    if ($table) {
                        app(ColumnRelevanceService::class)->ensureSingleRelevantColumn($table);
                    }
                }
            }
        }

        $this->projectService->syncCategories($project, $request->collect('assignedCategoryIds'), $request->input('mainCategoryId'));
        $this->projectService->syncSectors($project, $request->collect('assignedSectorIds'), $request->input('mainSectorId'));
        $this->projectService->syncGenres($project, $request->collect('assignedGenreIds'), $request->input('mainGenreId'));

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

        // only persist role ids that actually exist; anything else would linger
        // in the project_user.roles JSON forever (role deletion can't clean it up)
        $validRoleIds = ProjectRole::pluck('id');
        $assignedUsers = collect($request->assigned_user_ids)->map(
            static function ($pivotData) use ($validRoleIds) {
                // plain user id without pivot attributes — leave untouched
                if (!is_array($pivotData)) {
                    return $pivotData;
                }

                $pivotData['roles'] = $validRoleIds->intersect($pivotData['roles'] ?? [])->values()->all();

                return $pivotData;
            }
        );

        $project->users()->sync($assignedUsers);
        $project->departments()->sync(collect($request->assigned_departments)->pluck('id'));

        // CRM-Kontakte im Team nur synchronisieren, wenn das globale Setting aktiv ist und der
        // Payload den Key enthält — sonst würden bestehende Verknüpfungen still gelöscht
        if (
            app(ProjectCreateSettings::class)->crm_contacts_in_team
            && $request->has('assigned_crm_contact_ids')
        ) {
            $assignedCrmContacts = collect($request->assigned_crm_contact_ids)->map(
                static function ($pivotData) use ($validRoleIds) {
                    if (!is_array($pivotData)) {
                        return $pivotData;
                    }

                    return [
                        'roles' => $validRoleIds->intersect($pivotData['roles'] ?? [])->values()->all(),
                    ];
                }
            );

            $project->teamCrmContacts()->sync($assignedCrmContacts);
        }

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

        // Broadcast team update event
        broadcast(new ProjectTeamUpdated($project->id));

        return Redirect::back();
    }

    public function updateAttributes(Request $request, Project $project): JsonResponse|RedirectResponse
    {
        $mainCategoryId = $request->input('mainCategoryId');
        $mainGenreId = $request->input('mainGenreId');
        $mainSectorId = $request->input('mainSectorId');

        // Cache schema checks in a static variable to avoid repeated DB introspection per request
        static $schemaCache = [];
        if (empty($schemaCache)) {
            $schemaCache['category_is_main'] = Schema::hasColumn('category_project', 'is_main');
            $schemaCache['genre_is_main'] = Schema::hasColumn('genre_project', 'is_main');
            $schemaCache['sector_is_main'] = Schema::hasColumn('project_sector', 'is_main');
        }
        $hasCategoryIsMain = $schemaCache['category_is_main'];
        $hasGenreIsMain = $schemaCache['genre_is_main'];
        $hasSectorIsMain = $schemaCache['sector_is_main'];

        $oldProjectCategories = $project->categories()->get();
        $categorySyncData = [];
        foreach (($request->assignedCategoryIds ?? []) as $id) {
            $categorySyncData[$id] = $hasCategoryIsMain
                ? ['is_main' => $mainCategoryId !== null && (int) $id === (int) $mainCategoryId]
                : [];
        }
        $project->categories()->sync($categorySyncData);
        $this->checkProjectCategoryChanges($project->id, $oldProjectCategories, $project->categories()->get());

        $oldProjectGenres = $project->genres()->get();
        $genreSyncData = [];
        foreach (($request->assignedGenreIds ?? []) as $id) {
            $genreSyncData[$id] = $hasGenreIsMain
                ? ['is_main' => $mainGenreId !== null && (int) $id === (int) $mainGenreId]
                : [];
        }
        $project->genres()->sync($genreSyncData);
        $this->checkProjectGenreChanges($project->id, $oldProjectGenres, $project->genres()->get());

        $oldProjectSectors = $project->sectors()->get();
        $sectorSyncData = [];
        foreach (($request->assignedSectorIds ?? []) as $id) {
            $sectorSyncData[$id] = $hasSectorIsMain
                ? ['is_main' => $mainSectorId !== null && (int) $id === (int) $mainSectorId]
                : [];
        }
        $project->sectors()->sync($sectorSyncData);
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

        // Ensure all projects being added to the group have exactly one budget-relevant column
        foreach ($projectIdsToAdd as $projectId) {
            $project = Project::find($projectId);
            if ($project) {
                $table = $project->table()->first();
                if ($table) {
                    app(ColumnRelevanceService::class)->ensureSingleRelevantColumn($table);
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
                    'id' => Str::uuid()->toString(),
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
                    'id' => Str::uuid()->toString(),
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
                if ($user === null) {
                    continue;
                }
                $notificationTitle = __('notification.project.leader.remove', [
                    'project' => $project->name
                ], $user->language);
                $broadcastMessage = [
                    'id' => Str::uuid()->toString(),
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
                if ($user === null) {
                    continue;
                }
                $notificationTitle = __('notification.project.budget.remove', [
                    'project' => $project->name
                ], $user->language);
                $broadcastMessage = [
                    'id' => Str::uuid()->toString(),
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
                if ($user === null) {
                    continue;
                }
                $notificationTitle = __('notification.project.member.add', [
                    'project' => $project->name
                ], $user->language);
                $broadcastMessage = [
                    'id' => Str::uuid()->toString(),
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
                if ($user === null) {
                    continue;
                }
                $notificationTitle = __('notification.project.member.remove', [
                    'project' => $project->name
                ], $user->language);
                $broadcastMessage = [
                    'id' => Str::uuid()->toString(),
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
            'is_group' => $project->is_group,
        ]);

        $this->budgetService->generateBasicBudgetValues(
            $newProject,
        );

        $newProject->categories()->sync($project->categories->pluck('id'));
        $newProject->sectors()->sync($project->sectors->pluck('id'));
        $newProject->genres()->sync($project->genres->pluck('id'));
        $newProject->departments()->sync($project->departments->pluck('id'));

        // Copy team including pivot flags (access_budget, is_manager, can_write, ...)
        // and make sure the duplicating user keeps access to the copy.
        $usersWithPivotFlags = $project->users->mapWithKeys(fn($user) => [
            $user->id => [
                'access_budget' => $user->pivot->access_budget,
                'is_manager' => $user->pivot->is_manager,
                'can_write' => $user->pivot->can_write,
                'delete_permission' => $user->pivot->delete_permission,
                'roles' => $user->pivot->roles,
            ],
        ])->all();

        if (!array_key_exists(Auth::id(), $usersWithPivotFlags)) {
            $usersWithPivotFlags[Auth::id()] = ['access_budget' => true];
        }

        $newProject->users()->sync($usersWithPivotFlags);

        if ($projectTab = $this->projectTabService->findFirstProjectTabWithShiftsComponent()) {
            return Redirect::route('projects.tab', [$newProject->id, $projectTab->id]);
        }

        return redirect()->route('projects', [
            'page' => $request->get('page'),
            'query' => $request->get('query'),
        ]);
    }

    public function destroy(
        Project $project,
        Request $request
    ): RedirectResponse {
        $this->authorize('delete', $project);
        // Single, consolidated notification instead of one per deleted event.
        $eventCount = $project->events()->count();

        foreach ($project->users()->get() as $user) {
            $notificationTitle = __('notification.project.delete_with_events', [
                'project' => $project->name,
                'count' => $eventCount,
            ], $user->language);
            $broadcastMessage = [
                'id' => Str::uuid()->toString(),
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

        // Soft-delete the project row itself synchronously so it disappears from the overview
        // immediately on reload. The heavy cascade (events, shifts, sub-events, timelines,
        // budget table, ...) is offloaded to a queued job so the request still returns fast –
        // projects with very many events (e.g. 10k) would otherwise exceed the PHP/HTTP
        // timeout. Requires an async QUEUE_CONNECTION (not "sync") and a running queue worker.
        $project->delete();

        SoftDeleteProjectJob::dispatch($project->id, Auth::id());

        return redirect()->route('projects', [
            'page' => $request->get('page'),
            'query' => $request->get('query'),
        ]);
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'project_ids' => 'required|array|min:1',
            'project_ids.*' => 'integer|exists:projects,id',
        ]);

        $projects = Project::whereIn('id', $validated['project_ids'])->get();

        foreach ($projects as $project) {
            // Skip projects the user is not allowed to delete instead of failing the whole batch.
            if (Auth::user()?->cannot('delete', $project)) {
                continue;
            }

            $eventCount = $project->events()->count();

            foreach ($project->users()->get() as $user) {
                $notificationTitle = __('notification.project.delete_with_events', [
                    'project' => $project->name,
                    'count' => $eventCount,
                ], $user->language);
                $broadcastMessage = [
                    'id' => Str::uuid()->toString(),
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

            // Same strategy as destroy(): soft-delete synchronously, offload the cascade to a job.
            $project->delete();
            SoftDeleteProjectJob::dispatch($project->id, Auth::id());
        }

        return redirect()->route('projects', [
            'page' => $request->get('page'),
            'query' => $request->get('query'),
        ]);
    }

    public function forceDelete(int $id): RedirectResponse
    {
        // 404 for ids that don't reference a trashed project (avoids queueing junk jobs).
        $project = Project::onlyTrashed()->findOrFail($id);
        $this->authorize('delete', $project);

        // Offloaded to a queued job: the cascade (events, shifts, sub-events, timelines,
        // budget table, ...) would exceed the PHP/HTTP timeout for projects with very many
        // events. Requires an async QUEUE_CONNECTION (not "sync") and a running queue worker.
        ForceDeleteProjectJob::dispatch($project->id, Auth::id());

        return Redirect::route('projects.trashed');
    }

    public function forceDeleteAll(): RedirectResponse
    {
        abort_unless(Auth::user()?->can(PermissionEnum::PROJECT_DELETE->value), 403);
        // One bounded job per trashed project instead of force-deleting everything inline.
        Project::onlyTrashed()
            ->pluck('id')
            ->each(fn (int $projectId) => ForceDeleteProjectJob::dispatch($projectId, Auth::id()));

        return Redirect::route('projects.trashed');
    }

    public function forceDeleteAllSettings(): RedirectResponse
    {
        Genre::onlyTrashed()->forceDelete();
        Category::onlyTrashed()->forceDelete();
        Sector::onlyTrashed()->forceDelete();
        ProjectState::onlyTrashed()->forceDelete();
        ContractType::onlyTrashed()->forceDelete();
        CompanyType::onlyTrashed()->forceDelete();
        Currency::onlyTrashed()->forceDelete();
        return Redirect::route('projects.settings.trashed');
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
        $this->authorize('delete', $project);

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
            'trashed_currencies' => Currency::onlyTrashed()->get(),
        ]);
    }

    public function getTrashed(Request $request): Response|ResponseFactory
    {
        $search = trim((string) $request->input('search', ''));
        $perPage = (int) $request->input('entitiesPerPage', 25);

        $trashedProjects = Project::onlyTrashed()
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%' . $search . '%'))
            ->orderByDesc('deleted_at')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Project $project) => (new ProjectIndexResource($project))->resolve());

        return inertia('Trash/Projects', [
            'trashed_projects' => $trashedProjects
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

    public function softTable(
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
        $tableService->softDelete(
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

    public function restoreTable(
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

        $tableService->restore(
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

        broadcast(new UpdateBudget($mainPosition->table->project_id));

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

        $projectId = $subPosition->mainPosition->table->project_id;

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

        broadcast(new UpdateBudget($projectId));

        return Redirect::back();
    }

    public function updateCommentedStatusOfRow(Request $request, SubPositionRow $row): void
    {
        $row->update(['commented' => $request->commented]);

        $cellIds = $row->cells->skip(3)->pluck('id');

        $row->cells()->whereIntegerInRaw('id', $cellIds)->update(['commented' => $request->commented]);
        broadcast(new UpdateBudget($row->subPosition->mainPosition->table->project_id));
        //return Redirect::back();
    }

    public function updateCommentedStatusOfCell(Request $request, ColumnCell $columnCell): void
    {
        $columnCell->update(['commented' => $request->commented]);
        broadcast(new UpdateBudget($columnCell->column->table->project_id));
        //return Redirect::back();
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

    public function duplicateColumn(Column $column, ColumnRelevanceService $columnRelevanceService): void
    {
        $newColumn = $column->replicate();
        $newColumn->relevant_for_project_groups = false;
        $newColumn->save();
        $newColumn->update(['name' => $column->name . ' (Kopie)']);
        $newColumn->cells()->forceDelete();
        // Verifizierungen und Finanzierungsquellen-Verknüpfungen dürfen nicht in die
        // Kopie wandern (sonst gilt die Kopie als verifiziert bzw. zählt doppelt auf
        // die Quelle) - analog zu addColumn.
        $newColumn->cells()->createMany(
            $column->cells()->get()->map(fn(ColumnCell $cell) => array_merge($cell->toArray(), [
                'verified_value' => null,
                'linked_money_source_id' => null,
                'linked_type' => null,
            ]))->all()
        );

        // Summen-Gerüst wie bei addColumn anlegen, sonst fehlen der Kopie die Summenzeilen.
        $tableId = $column->table_id;
        $newColumn->subPositionSumDetails()->createMany(
            SubPosition::whereHas('mainPosition', fn(Builder $query) => $query->where('table_id', $tableId))
                ->pluck('id')
                ->map(fn(int $subPositionId) => ['sub_position_id' => $subPositionId])
                ->all()
        );
        $newColumn->mainPositionSumDetails()->createMany(
            MainPosition::where('table_id', $tableId)
                ->pluck('id')
                ->map(fn(int $mainPositionId) => ['main_position_id' => $mainPositionId])
                ->all()
        );
        $newColumn->budgetSumDetails()->create(['type' => 'COST']);
        $newColumn->budgetSumDetails()->create(['type' => 'EARNING']);

        // Die neueste Wertspalte gilt als aktueller Planungsstand und wird budgetrelevant.
        if ($columnRelevanceService->isFlaggable($newColumn)) {
            $columnRelevanceService->assignExclusive($newColumn);
        }

        broadcast(new UpdateBudget($column->table->project_id));
    }

    public function duplicateSubPosition(SubPosition $subPosition, $mainPositionId = null): void
    {
        $newSubPosition = $subPosition->replicate();
        $newSubPosition->save();
        $newSubPosition->update(['name' => $subPosition->name . ' (Kopie)']);

        if ($mainPositionId !== null) {
            $newSubPosition->update(['main_position_id' => $mainPositionId]);
        }

        // Eager load cells to avoid N+1 queries during duplication
        $rows = $subPosition->subPositionRows()->with('cells')->get();
        foreach ($rows as $subPositionRow) {
            $newSubPositionRow = $subPositionRow->replicate();
            $newSubPositionRow->name = $subPositionRow->name . ' (Kopie)';
            $newSubPositionRow->sub_position_id = $newSubPosition->id;
            $newSubPositionRow->save();
            $newSubPositionRow->cells()->forceDelete();

            // Verifizierungen/Finanzierungsquellen nicht mitkopieren (s. duplicateColumn)
            $cellsData = $subPositionRow->cells->map(fn($cell) => array_merge($cell->toArray(), [
                'verified_value' => null,
                'linked_money_source_id' => null,
                'linked_type' => null,
            ]))->toArray();
            if (!empty($cellsData)) {
                $newSubPositionRow->cells()->createMany($cellsData);
            }
        }

        broadcast(new UpdateBudget($subPosition->mainPosition->table->project_id));
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

        broadcast(new UpdateBudget($mainPosition->table->project_id));
    }

    public function duplicateRow(SubPositionRow $subPositionRow): void
    {
        $subPositionRowReplicate = $subPositionRow->replicate();
        $subPositionRowReplicate->sub_position_id = $subPositionRow->subPosition->id;
        $subPositionRowReplicate->save();

        foreach ($subPositionRow->cells as $subPositionRowCell) {
            $subPositionRowCellReplicate = $subPositionRowCell->replicate();
            $subPositionRowCellReplicate->sub_position_row_id = $subPositionRowReplicate->id;
            // Verifizierungen/Finanzierungsquellen nicht mitkopieren (s. duplicateColumn)
            $subPositionRowCellReplicate->verified_value = null;
            $subPositionRowCellReplicate->linked_money_source_id = null;
            $subPositionRowCellReplicate->linked_type = null;
            $subPositionRowCellReplicate->save();
        }
        broadcast(new UpdateBudget($subPositionRow->subPosition->mainPosition->table->project_id));
    }

    public function updateCommentedStatusOfColumn(Request $request, Column $column): void
    {
        $validated = $request->validate(['commented' => 'required|boolean']);
        $column->update(['commented' => $validated['commented']]);

        broadcast(new UpdateBudget($column->table->project_id));
    }

    public function projectBudgetExport(Project $project): BinaryFileResponse
    {
        return (new BudgetExport($project))
            ->download(
                sprintf(
                    '%s_budget_stand_%s.xlsx',
                    Str::snake(str_replace(['/', '\\'], '_', $project->name)),
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
            $request->validate([
                'desiredColumns' => ['nullable', 'array', 'min:1'],
                'desiredColumns.*' => [
                    'string',
                    Rule::in(array_keys(BudgetsByBudgetDeadlineExport::availableColumns())),
                ],
            ]);

            return (new BudgetsByBudgetDeadlineExport(
                $startBudgetDeadline,
                $endBudgetDeadline,
                $request->filled('desiredColumns') ? $request->input('desiredColumns') : null,
            ))
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
            $request->validate([
                'desiredColumns' => ['nullable', 'array', 'min:1'],
                'desiredColumns.*' => [
                    'string',
                    Rule::in(DetailedBudgetsByBudgetDeadlineExport::availableColumnKeys()),
                ],
            ]);

            $generalSettings = app(\Artwork\Modules\GeneralSettings\Models\GeneralSettings::class);
            $budgetColumnSettings = \Artwork\Modules\Budget\Models\BudgetColumnSetting::all();
            return (new DetailedBudgetsByBudgetDeadlineExport(
                $startBudgetDeadline,
                $endBudgetDeadline,
                $generalSettings->budget_account_management_global,
                $budgetColumnSettings,
                $request->filled('desiredColumns') ? $request->input('desiredColumns') : null,
            ))
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
            'gema' => $request->boolean('gema'),
            'cost_center_description' => $request->description,
        ]);

        $this->checkProjectCostCenterChanges($project->id, $oldCostCenter, $costCenter->id ?? null);

        return Redirect::back();
    }

    public function updateCostCenter(Request $request, Project $project): RedirectResponse
    {
        $oldCostCenter = $project->cost_center_id;
        $costCenter = null;

        if (!empty($request->cost_center_name)) {
            $costCenter = CostCenter::firstOrCreate(['name' => $request->cost_center_name]);
        }

        $project->update([
            'cost_center_id' => $costCenter->id ?? null,
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
                        'first_and_last_event_date' => $project->first_and_last_event_date,
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

    /**
     * Returns the rooms this project actually occupies (via its events and shifts),
     * each with the room-specific period: earliest start and latest end across all
     * events and shifts of the project in that room.
     *
     * Used by the material-issue modal to offer the project's rooms as quick-select
     * chips and to narrow the issue period to a single room's span.
     *
     * @return array<int, array<string, mixed>>
     */
    public function roomsWithEventPeriods(Project $project): array
    {
        // room_id => ['start' => Carbon, 'end' => Carbon]
        $periods = [];

        $accumulate = static function (?Carbon $start, ?Carbon $end, ?int $roomId) use (&$periods): void {
            if (!$roomId || !$start || !$end) {
                return;
            }
            if (!isset($periods[$roomId])) {
                $periods[$roomId] = ['start' => $start, 'end' => $end];
                return;
            }
            if ($start->lt($periods[$roomId]['start'])) {
                $periods[$roomId]['start'] = $start;
            }
            if ($end->gt($periods[$roomId]['end'])) {
                $periods[$roomId]['end'] = $end;
            }
        };

        // Events of this project (room + datetime span).
        Event::query()
            ->where('project_id', $project->id)
            ->whereNotNull('room_id')
            ->get(['id', 'room_id', 'start_time', 'end_time'])
            ->each(function (Event $event) use ($accumulate): void {
                $start = $event->start_time ? Carbon::parse($event->start_time) : null;
                $end = $event->end_time ? Carbon::parse($event->end_time) : null;
                $accumulate($start, $end, $event->room_id ? (int) $event->room_id : null);
            });

        // Shifts of this project: either standalone (project_id set) or event-bound
        // (their event belongs to the project). Room falls back to the event's room.
        Shift::query()
            ->where(function ($query) use ($project): void {
                $query->where('project_id', $project->id)
                    ->orWhereHas('event', fn ($q) => $q->where('project_id', $project->id));
            })
            ->with('event:id,room_id')
            ->get(['id', 'room_id', 'event_id', 'start_date', 'end_date', 'start', 'end'])
            ->each(function (Shift $shift) use ($accumulate): void {
                $roomId = $shift->room_id ?? $shift->event?->room_id;
                $startDate = $shift->start_date ? Carbon::parse($shift->start_date)->toDateString() : null;
                $endDate = $shift->end_date ? Carbon::parse($shift->end_date)->toDateString() : null;
                $start = $startDate ? Carbon::parse($startDate . ' ' . ($shift->start ?: '00:00')) : null;
                $end = $endDate ? Carbon::parse($endDate . ' ' . ($shift->end ?: '23:59')) : null;
                $accumulate($start, $end, $roomId ? (int) $roomId : null);
            });

        if (empty($periods)) {
            return [];
        }

        $rooms = Room::whereIn('id', array_keys($periods))->get(['id', 'name'])->keyBy('id');

        $result = [];
        foreach ($periods as $roomId => $period) {
            $room = $rooms->get($roomId);
            if (!$room) {
                continue;
            }
            $result[] = [
                'id' => (int) $roomId,
                'name' => $room->name,
                'start_date' => $period['start']->toDateString(),
                'start_time' => $period['start']->format('H:i'),
                'end_date' => $period['end']->toDateString(),
                'end_time' => $period['end']->format('H:i'),
            ];
        }

        // Earliest-starting room first.
        usort($result, static fn ($a, $b) => strcmp($a['start_date'] . $a['start_time'], $b['start_date'] . $b['start_time']));

        return $result;
    }

    /**
     * Liefert aus einer Liste von Projekt-IDs nur die zurück, die noch (nicht gelöscht)
     * existieren. Wird von der "Zuletzt geöffnete Projekte"-Komponente genutzt, deren
     * Liste clientseitig in localStorage liegt und sonst auf gelöschte Projekte verweisen
     * würde.
     *
     * @return array<int, int>
     */
    public function filterExistingProjectIds(Request $request): array
    {
        $ids = collect($request->input('ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return [];
        }

        return Project::whereIn('id', $ids)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function updateTimeline(Timeline $timeline, UpdateTimelineRequest $request): void
    {
        $this->timelineService->updateTimeline($timeline, collect($request->all()));
    }
}
