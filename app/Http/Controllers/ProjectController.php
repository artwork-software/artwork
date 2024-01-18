<?php

namespace App\Http\Controllers;

use App\Enums\BudgetTypesEnum;
use App\Enums\NotificationConstEnum;
use App\Enums\PermissionNameEnum;
use App\Enums\RoleNameEnum;
use App\Exports\ProjectBudgetExport;
use App\Exports\ProjectBudgetsByBudgetDeadlineExport;
use App\Http\Requests\SearchRequest;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Resources\ChecklistTemplateIndexResource;
use App\Http\Resources\EventTypeResource;
use App\Http\Resources\FreelancerDropResource;
use App\Http\Resources\ProjectCalendarShowEventResource;
use App\Http\Resources\ProjectEditResource;
use App\Http\Resources\ProjectIndexResource;
use App\Http\Resources\ProjectIndexShowResource;
use App\Http\Resources\ProjectResources\ProjectBudgetResource;
use App\Http\Resources\ProjectResources\ProjectCalendarResource;
use App\Http\Resources\ProjectResources\ProjectChecklistResource;
use App\Http\Resources\ProjectResources\ProjectCommentResource;
use App\Http\Resources\ProjectResources\ProjectInfoResource;
use App\Http\Resources\ProjectResources\ProjectShiftResource;
use App\Http\Resources\ResourceModels\CalendarEventCollectionResourceModel;
use App\Http\Resources\ServiceProviderDropResource;
use App\Http\Resources\UserDropResource;
use App\Http\Resources\UserIndexResource;
use App\Models\Category;
use App\Models\ChecklistTemplate;
use App\Models\CollectingSociety;
use App\Models\CompanyType;
use App\Models\ContractType;
use App\Models\Currency;
use App\Models\Event;
use App\Models\EventType;
use App\Models\Filter;
use App\Models\Freelancer;
use App\Models\Genre;
use App\Models\MoneySource;
use App\Models\Sector;
use App\Models\ServiceProvider;
use App\Models\TimeLine;
use App\Models\User;
use App\Support\Services\HistoryService;
use App\Support\Services\MoneySourceThresholdReminderService;
use App\Support\Services\NewHistoryService;
use App\Support\Services\NotificationService;
use Artwork\Modules\Budget\Models\BudgetSumDetails;
use Artwork\Modules\Budget\Models\CellCalculations;
use Artwork\Modules\Budget\Models\Column;
use Artwork\Modules\Budget\Models\ColumnCell;
use Artwork\Modules\Budget\Models\MainPosition;
use Artwork\Modules\Budget\Models\MainPositionDetails;
use Artwork\Modules\Budget\Models\SubPosition;
use Artwork\Modules\Budget\Models\SubPositionRow;
use Artwork\Modules\Budget\Models\SubpositionSumDetail;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\Budget\Services\BudgetService;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Department\Models\Department;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectStates;
use Artwork\Modules\Project\Services\ProjectGroupService;
use Artwork\Modules\Project\Services\ProjectService;
use Artwork\Modules\Room\Models\Room;
use Carbon\Carbon;
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
use Inertia\Response;
use Inertia\ResponseFactory;
use Intervention\Image\Facades\Image;
use stdClass;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectController extends Controller
{
    // init empty notification controller
    protected ?NotificationService $notificationService = null;

    protected ?stdClass $notificationData = null;

    protected ?NewHistoryService $history = null;

    protected ?SchedulingController $schedulingController = null;
    public function __construct(
        private readonly ProjectService $projectService,
        private readonly BudgetService $budgetService,
    ) {
        // init notification controller
        $this->notificationService = new NotificationService();
        $this->notificationData = new stdClass();
        $this->notificationData->project = new stdClass();
        $this->notificationData->type = NotificationConstEnum::NOTIFICATION_PROJECT;
        $this->history = new NewHistoryService('Artwork\Modules\Project\Models\Project');
        $this->schedulingController = new SchedulingController();
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

    public function index(): Response|ResponseFactory
    {
        $projects = Project::query()
            ->with([
                'checklists.tasks.checklist.project',
                'access_budget',
                'categories',
                'comments.user',
                'departments.users.departments',
                'genres',
                'managerUsers',
                'project_files',
                'sectors',
                'users.departments',
                'writeUsers',
                'state',
                'delete_permission_users'
            ])
            ->orderBy('id', 'DESC')
            ->get();

        return inertia('Projects/ProjectManagement', [
            'projects' => ProjectIndexShowResource::collection($projects)->resolve(),
            'states' => ProjectStates::all(),
            'projectGroups' => Project::where('is_group', 1)->with('groups')->get(),

            'users' => User::all(),

            'categories' => Category::query()->with('projects')->get()->map(fn($category) => [
                'id' => $category->id,
                'name' => $category->name,
                'projects' => $category->projects
            ]),

            'genres' => Genre::query()->with('projects')->get()->map(fn($genre) => [
                'id' => $genre->id,
                'name' => $genre->name,
                'projects' => $genre->projects
            ]),

            'sectors' => Sector::query()->with('projects')->get()->map(fn($sector) => [
                'id' => $sector->id,
                'name' => $sector->name,
                'projects' => $sector->projects
            ]),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function searchDepartmentsAndUsers(SearchRequest $request): array
    {
        // TODO: Hier bitte gucken wie man die Policy wieder zum laufen bekommt
        /*$this->authorize('viewAny', Department::class);
        $this->authorize('viewAny', User::class);*/

        return [
            'departments' => Department::search($request->input('query'))->get(),
            'users' => UserIndexResource::collection(User::search($request->input('query'))->get())
        ];
    }

    /**
     * @return array<string, mixed>
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function search(SearchRequest $request): array
    {
        $this->authorize('viewAny', Project::class);
        $projects = Project::search($request->input('query'))->get();


        return ProjectIndexResource::collection($projects)->resolve();
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


    public function store(StoreProjectRequest $request): JsonResponse|RedirectResponse
    {
        if (
            !Auth::user()->canAny(
                [
                    PermissionNameEnum::ADD_EDIT_OWN_PROJECT->value,
                    PermissionNameEnum::WRITE_PROJECTS->value,
                    PermissionNameEnum::PROJECT_MANAGEMENT->value
                ]
            )
        ) {
            return response()->json(['error' => 'Not authorized to assign users to a project.'], 403);
        }

        $departments = collect($request->assigned_departments)
            ->map(fn($department) => Department::query()->findOrFail($department['id']));
        //@todo how did this line ever work?
        //->map(fn(Department $department) => $this->authorize('update', $department));



        //$this->projectService->storeByRequest($request);


        $project = Project::create([
            'name' => $request->name,
            'description' => $request->description,
            'number_of_participants' => $request->number_of_participants,
            'cost_center' => $request->cost_center,
            'budget_deadline' => $request->budgetDeadline
        ]);

        $project->users()->save(
            Auth::user(),
            ['access_budget' => true, 'is_manager' => false, 'can_write' => true, 'delete_permission' => true]
        );

        if ($request->isGroup) {
            $project->is_group = true;
            $project->groups()->sync(collect($request->projects));
            $project->save();
        }

        if (!$request->isGroup && !empty($request->selectedGroup)) {
            $group = Project::find($request->selectedGroup['id']);
            $group->groups()->syncWithoutDetaching($project->id);
        }

        if ($request->assigned_user_ids) {
            $project->users()->sync(collect($request->assigned_user_ids));
        }

        $project->categories()->sync($request->assignedCategoryIds);
        $project->sectors()->sync($request->assignedSectorIds);
        $project->genres()->sync($request->assignedGenreIds);
        $project->departments()->sync($departments->pluck('id'));

        //$this->generateBasicBudgetValues($project);

        $this->budgetService->generateBasicBudgetValues($project);

        $eventRelevantEventTypeIds = EventType::where('relevant_for_shift', true)->pluck('id')->toArray();
        $project->shiftRelevantEventTypes()->sync(collect($eventRelevantEventTypeIds));

        return Redirect::route('projects', $project)->with('success', 'Project created.');
    }

    //@todo: fix phpcs error - refactor function because complexity is rising
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
    public function updateEntranceData(Project $project, Request $request)
    {
        $oldNumOfGuest = $project->num_of_guests;
        $oldEntryFee = $project->entry_fee;
        $oldRegistrationRequired = $project->registration_required;
        $oldRegisterBy = $project->register_by;
        $oldRegistrationDeadline = $project->registration_deadline;
        $oldClosedSociety = $project->closed_society;

        $project->update(array_filter($request->all(), function ($field) {
            return !is_null($field) || empty($field);
        }));

        $newNumOfGuest = $project->num_of_guests;
        $newEntryFee = $project->entry_fee;
        $newRegistrationRequired = $project->registration_required;
        $newRegisterBy = $project->register_by;
        $newRegistrationDeadline = $project->registration_deadline;
        $newClosedSociety = $project->closed_society;


        // Geändert
        if ($oldNumOfGuest !== $newNumOfGuest && $oldNumOfGuest !== null && $newNumOfGuest !== null) {
            $this->history->createHistory($project->id, 'Eintritt und Anmeldung wurde geändert', 'public_changes');
        }

        if ($oldClosedSociety !== $newClosedSociety && $oldClosedSociety !== null && $oldClosedSociety !== null) {
            $this->history->createHistory($project->id, 'Eintritt und Anmeldung wurde geändert', 'public_changes');
        }

        if ($oldEntryFee !== $newEntryFee && $oldEntryFee !== null && $oldEntryFee !== null) {
            $this->history->createHistory($project->id, 'Eintritt und Anmeldung wurde geändert', 'public_changes');
        }

        if ($oldRegisterBy !== $newRegisterBy && $oldRegisterBy !== null && $oldRegisterBy !== null) {
            $this->history->createHistory($project->id, 'Eintritt und Anmeldung wurde geändert', 'public_changes');
        }

        if (
            $oldRegistrationRequired !== $newRegistrationRequired &&
            $oldRegistrationRequired !== null &&
            $oldRegistrationRequired !== null
        ) {
            $this->history->createHistory($project->id, 'Eintritt und Anmeldung wurde geändert', 'public_changes');
        }

        if (
            $oldRegistrationDeadline !== $newRegistrationDeadline &&
            $oldRegistrationDeadline !== null &&
            $oldRegistrationDeadline !== null
        ) {
            $this->history->createHistory($project->id, 'Eintritt und Anmeldung wurde geändert', 'public_changes');
        }

        // enfernt
        if ($oldNumOfGuest !== null && $newNumOfGuest === null) {
            $this->history->createHistory($project->id, 'Eintritt und Anmeldung wurde entfernt', 'public_changes');
        }
        if ($oldClosedSociety !== null && $newClosedSociety === null) {
            $this->history->createHistory($project->id, 'Eintritt und Anmeldung wurde entfernt', 'public_changes');
        }
        if ($oldEntryFee !== null && $newEntryFee === null) {
            $this->history->createHistory($project->id, 'Eintritt und Anmeldung wurde entfernt', 'public_changes');
        }
        if ($oldRegisterBy !== null && $newRegisterBy === null) {
            $this->history->createHistory($project->id, 'Eintritt und Anmeldung wurde entfernt', 'public_changes');
        }
        if ($oldRegistrationRequired !== null && $newRegistrationRequired === null) {
            $this->history->createHistory($project->id, 'Eintritt und Anmeldung wurde entfernt', 'public_changes');
        }
        if ($oldRegistrationDeadline !== null && $newRegistrationDeadline === null) {
            $this->history->createHistory($project->id, 'Eintritt und Anmeldung wurde entfernt', 'public_changes');
        }


        // hinzugefügt
        if ($oldNumOfGuest === null && $newNumOfGuest !== null) {
            $this->history->createHistory($project->id, 'Eintritt und Anmeldung wurde hinzugefügt', 'public_changes');
        }
        if ($oldClosedSociety === null && $newClosedSociety !== null) {
            $this->history->createHistory($project->id, 'Eintritt und Anmeldung wurde hinzugefügt', 'public_changes');
        }
        if ($oldEntryFee === null && $newEntryFee !== null) {
            $this->history->createHistory($project->id, 'Eintritt und Anmeldung wurde hinzugefügt', 'public_changes');
        }
        if ($oldRegisterBy === null && $newRegisterBy !== null) {
            $this->history->createHistory($project->id, 'Eintritt und Anmeldung wurde hinzugefügt', 'public_changes');
        }
        if ($oldRegistrationRequired === null && $newRegistrationRequired !== null) {
            $this->history->createHistory($project->id, 'Eintritt und Anmeldung wurde hinzugefügt', 'public_changes');
        }
        if ($oldRegistrationDeadline === null && $newRegistrationDeadline !== null) {
            $this->history->createHistory($project->id, 'Eintritt und Anmeldung wurde hinzugefügt', 'public_changes');
        }

        $this->setPublicChangesNotification($project->id);

        return Redirect::back();
    }

    public function generateBasicBudgetValues(Project $project): void
    {
        $table = $project->table()->create([
            'name' => $project->name . ' Budgettabelle'
        ]);

        $columns = $table->columns()->createMany([
            [
                'name' => 'KTO',
                'subName' => '',
                'type' => 'empty',
                'linked_first_column' => null,
                'linked_second_column' => null
            ],
            [
                'name' => 'A',
                'subName' => '',
                'type' => 'empty',
                'linked_first_column' => null,
                'linked_second_column' => null
            ],
            [
                'name' => 'Position',
                'subName' => '',
                'type' => 'empty',
                'linked_first_column' => null,
                'linked_second_column' => null
            ],
            [
                'name' => date('Y') . ' €',
                'subName' => 'A',
                'type' => 'empty',
                'linked_first_column' => null,
                'linked_second_column' => null
            ],
        ]);

        $costMainPosition = $table->mainPositions()->create([
            'type' => BudgetTypesEnum::BUDGET_TYPE_COST,
            'name' => 'Hauptpostion',
            'position' => $table->mainPositions()
                    ->where('type', BudgetTypesEnum::BUDGET_TYPE_COST)->max('position') + 1
        ]);

        $earningMainPosition = $table->mainPositions()->create([
            'type' => BudgetTypesEnum::BUDGET_TYPE_EARNING,
            'name' => 'Hauptpostion',
            'position' => $table->mainPositions()
                    ->where('type', BudgetTypesEnum::BUDGET_TYPE_EARNING)->max('position') + 1
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

        $costSubPositionRow->columns()->attach($firstThreeColumns->pluck('id'), [
            'value' => "",
            'verified_value' => "",
            'linked_money_source_id' => null,
        ]);

        $earningSubPositionRow->columns()->attach($firstThreeColumns->pluck('id'), [
            'value' => "",
            'verified_value' => "",
            'linked_money_source_id' => null,
        ]);

        $costSubPositionRow->columns()->attach($columns->pluck('id'), [
            'value' => 0,
            'verified_value' => null,
            'linked_money_source_id' => null,
        ]);

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

        $earningSubPositionRow->columns()->attach($columns->pluck('id'), [
            'value' => 0,
            'verified_value' => null,
            'linked_money_source_id' => null,
        ]);
    }

    public function verifiedRequestMainPosition(Request $request): RedirectResponse
    {

        $mainPosition = MainPosition::find($request->id);
        $project = $mainPosition->table()->first()->project()->first();

        if ($request->giveBudgetAccess) {
            $project->users()->updateExistingPivot($request->user, ['access_budget' => true]);
            $user = User::find($request->user);
            $notificationTitle = 'Du hast Budgetzugriff in ' . $project->name . ' erhalten';
            $broadcastMessage = [
                'id' => rand(1, 1000000),
                'type' => 'success',
                'message' => $notificationTitle
            ];

            $this->notificationService->setTitle($notificationTitle);
            $this->notificationService->setIcon('green');
            $this->notificationService->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_PROJECT);
            $this->notificationService->setBroadcastMessage($broadcastMessage);
            $this->notificationService->setNotificationTo($user);
            $this->notificationService->createNotification();
        }
        $mainPosition->update(['is_verified' => BudgetTypesEnum::BUDGET_VERIFIED_TYPE_REQUESTED]);
        $notificationTitle = 'Neue Verifizierungsanfrage';
        $budgetData = new stdClass();
        $budgetData->position_id = $mainPosition->id;
        $budgetData->requested_by = Auth::id();
        $budgetData->changeType = BudgetTypesEnum::BUDGET_VERIFICATION_REQUEST;
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
                'href' => $project ? route('projects.show.budget', $project->id) : null,
            ]
        ];
        $this->notificationService->setTitle($notificationTitle);
        $this->notificationService->setIcon('blue');
        $this->notificationService->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_BUDGET_STATE_CHANGED);
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
        $this->history->createHistory(
            $project->id,
            'Hauptposition „' . $mainPosition->name . '“ zur Verifizierung angefragt',
            'budget'
        );
        return back()->with('success');
    }

    public function takeBackVerification(Request $request): RedirectResponse
    {
        $notificationTitle = 'Verifizierungsanfrage gelöscht';
        $budgetData = new stdClass();
        $budgetData->requested_by = Auth::id();
        $budgetData->changeType = BudgetTypesEnum::BUDGET_VERIFICATION_TAKE_BACK;
        if ($request->type === 'main') {
            $mainPosition = MainPosition::find($request->position['id']);
            $verifiedRequest = $mainPosition->verified()->first();
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
                    'href' => $project ? route('projects.show.budget', $project->id) : null,
                ]
            ];
            $this->notificationService->setTitle($notificationTitle);
            $this->notificationService->setIcon('red');
            $this->notificationService
                ->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_BUDGET_STATE_CHANGED);
            $this->notificationService->setBroadcastMessage($broadcastMessage);
            $this->notificationService->setBudgetData($budgetData);
            $this->notificationService->setProjectId($project->id);
            $this->notificationService->setDescription($notificationDescription);
            $this->notificationService->setNotificationTo(User::find($verifiedRequest->requested));
            $this->notificationService->createNotification();
            $verifiedRequest->delete();
            $mainPosition->update(['is_verified' => BudgetTypesEnum::BUDGET_VERIFIED_TYPE_NOT_VERIFIED]);
            $this->history->createHistory(
                $project->id,
                'Hauptposition „' . $mainPosition->name . '“ Verifizierungsanfrage zurückgenommen',
                'budget'
            );
        }

        if ($request->type === 'sub') {
            $subPosition = SubPosition::find($request->position['id']);
            $mainPosition = $subPosition->mainPosition()->first();
            $verifiedRequest = $subPosition->verified()->first();
            $table = $mainPosition->table()->first();
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
                    'href' => $project ? route('projects.show.budget', $project->id) : null,
                ]
            ];

            $this->notificationService->setTitle($notificationTitle);
            $this->notificationService->setIcon('red');
            $this->notificationService
                ->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_BUDGET_STATE_CHANGED);
            $this->notificationService->setBroadcastMessage($broadcastMessage);
            $this->notificationService->setBudgetData($budgetData);
            $this->notificationService->setProjectId($project->id);
            $this->notificationService->setNotificationTo(User::find($verifiedRequest->requested));
            $this->notificationService->setDescription($notificationDescription);
            $this->notificationService->createNotification();
            $subPosition->update(['is_verified' => BudgetTypesEnum::BUDGET_VERIFIED_TYPE_NOT_VERIFIED]);
            $verifiedRequest->delete();
            $this->history->createHistory(
                $project->id,
                'Unterposition „' . $subPosition->name . '“ Verifizierungsanfrage zurückgenommen',
                'budget'
            );
        }
        return back()->with(['success']);
    }

    private function deleteOldNotification($positionId, $requestedId): void
    {
        DatabaseNotification::query()
            ->whereJsonContains("data->budgetData->position_id", $positionId)
            ->whereJsonContains("data->budgetData->requested_by", $requestedId)
            ->whereJsonContains("data->budgetData->changeType", BudgetTypesEnum::BUDGET_VERIFICATION_REQUEST)
            ->delete();
    }

    public function removeVerification(Request $request): RedirectResponse
    {
        $notificationTitle = 'Verifizierung in Budget aufgehoben';
        $budgetData = new stdClass();
        $budgetData->requested_by = Auth::id();
        $budgetData->changeType = BudgetTypesEnum::BUDGET_VERIFICATION_DELETED;

        if ($request->type === 'main') {
            $mainPosition = MainPosition::find($request->position['id']);
            $verifiedRequest = $mainPosition->verified()->first();
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
                    'href' => $project ? route('projects.show.budget', $project->id) : null,
                ]
            ];
            $this->notificationService->setTitle($notificationTitle);
            $this->notificationService->setIcon('red');
            $this->notificationService->setPriority(2);
            $this->notificationService
                ->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_BUDGET_STATE_CHANGED);
            $this->notificationService->setBroadcastMessage($broadcastMessage);
            $this->notificationService->setBudgetData($budgetData);
            $this->notificationService->setProjectId($project->id);
            $this->notificationService->setNotificationTo(User::find($verifiedRequest->requested));
            $this->notificationService->setDescription($notificationDescription);
            $this->notificationService->createNotification();
            $mainPosition->update(['is_verified' => BudgetTypesEnum::BUDGET_VERIFIED_TYPE_NOT_VERIFIED]);
            $verifiedRequest->delete();
            $this->history->createHistory(
                $project->id,
                'Hauptposition „' . $mainPosition->name . '“ Verifizierung aufgehoben',
                'budget'
            );
        }

        if ($request->type === 'sub') {
            $subPosition = SubPosition::find($request->position['id']);
            $mainPosition = $subPosition->mainPosition()->first();
            $verifiedRequest = $subPosition->verified()->first();
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
                    'href' => $project ? route('projects.show.budget', $project->id) : null,
                ]
            ];

            $this->notificationService->setTitle($notificationTitle);
            $this->notificationService->setIcon('red');
            $this->notificationService->setPriority(2);
            $this->notificationService
                ->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_BUDGET_STATE_CHANGED);
            $this->notificationService->setBroadcastMessage($broadcastMessage);
            $this->notificationService->setBudgetData($budgetData);
            $this->notificationService->setProjectId($project->id);
            $this->notificationService->setNotificationTo(User::find($verifiedRequest->requested));
            $this->notificationService->setDescription($notificationDescription);
            $this->notificationService->createNotification();
            $subPosition->update(['is_verified' => BudgetTypesEnum::BUDGET_VERIFIED_TYPE_NOT_VERIFIED]);
            $verifiedRequest->delete();
            $this->history->createHistory(
                $project->id,
                'Unterposition „' . $subPosition->name . '“ Verifizierung aufgehoben',
                'budget'
            );
        }

        return back()->with(['success']);
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
            $notificationTitle = 'Du hast Budgetzugriff in ' . $project->name . ' erhalten';
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
                    'href' => $project ? route('projects.show.budget', $project->id) : null,
                ]
            ];
            $this->notificationService->setTitle($notificationTitle);
            $this->notificationService->setIcon('red');
            $this->notificationService->setPriority(2);
            $this->notificationService
                ->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_BUDGET_STATE_CHANGED);
            $this->notificationService->setBroadcastMessage($broadcastMessage);
            $this->notificationService->setNotificationTo($user);
            $this->notificationService->setDescription($notificationDescription);
            $this->notificationService->createNotification();
        }
        $subPosition->update(['is_verified' => BudgetTypesEnum::BUDGET_VERIFIED_TYPE_REQUESTED]);
        $notificationTitle = 'Neue Verifizierungsanfrage';
        $budgetData = new stdClass();
        $budgetData->position_id = $subPosition->id;
        $budgetData->requested_by = Auth::id();
        $budgetData->changeType = BudgetTypesEnum::BUDGET_VERIFICATION_REQUEST;
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
                'href' => $project ? route('projects.show.budget', $project->id) : null,
            ]
        ];
        $this->notificationService->setTitle($notificationTitle);
        $this->notificationService->setIcon('blue');
        $this->notificationService->setPriority(1);
        $this->notificationService->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_BUDGET_STATE_CHANGED);
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

        $this->history->createHistory(
            $project->id,
            'Unterposition „' . $subPosition->name . '“ zur Verifizierung angefragt',
            'budget'
        );
        return back()->with('success');
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
            ->whereJsonContains("data->budgetData->changeType", BudgetTypesEnum::BUDGET_VERIFICATION_REQUEST)
            ->delete();

        $this->history->createHistory(
            $request->project_id,
            'Unterposition „' . $subPosition->name . '“ verifiziert',
            'budget'
        );

        return back()->with('success');
    }

    public function fixSubPosition(Request $request): RedirectResponse
    {
        $subPosition = SubPosition::find($request->subPositionId);
        $this->setSubPositionCellVerifiedValue($subPosition);
        $subPosition->update(['is_fixed' => true]);
        $notificationTitle = 'Budget festgeschrieben';
        $project = Project::find($request->project_id);
        $budgetData = new stdClass();
        $budgetData->position_id = $subPosition->id;
        $budgetData->requested_by = Auth::id();
        $budgetData->changeType = BudgetTypesEnum::BUDGET_VERIFICATION_REQUEST;
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
                'href' => $project ? route('projects.show.budget', $project->id) : null,
            ]
        ];
        $this->notificationService->setTitle($notificationTitle);
        $this->notificationService->setIcon('red');
        $this->notificationService->setPriority(2);
        $this->notificationService->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_BUDGET_STATE_CHANGED);
        $this->notificationService->setBroadcastMessage($broadcastMessage);
        $this->notificationService->setBudgetData($budgetData);
        $this->notificationService->setDescription($notificationDescription);

        foreach ($project->access_budget()->get() as $user) {
            $this->notificationService->setNotificationTo($user);
            $this->notificationService->createNotification();
        }

        $this->history->createHistory(
            $project->id,
            'Unterposition „' . $subPosition->name . '“ festgeschrieben',
            'budget'
        );

        return back()->with('success');
    }

    public function unfixSubPosition(Request $request): RedirectResponse
    {
        $subPosition = SubPosition::find($request->subPositionId);
        $this->removeSubPositionCellVerifiedValue($subPosition);
        $subPosition->update(['is_fixed' => false]);
        $notificationTitle = 'Festschreibung in Budget aufgehoben';
        $project = Project::find($request->project_id);
        $budgetData = new stdClass();
        $budgetData->position_id = $subPosition->id;
        $budgetData->requested_by = Auth::id();
        $budgetData->changeType = BudgetTypesEnum::BUDGET_VERIFICATION_REQUEST;
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
                'href' => $project ? route('projects.show.budget', $project->id) : null,
            ]
        ];

        $this->notificationService->setTitle($notificationTitle);
        $this->notificationService->setIcon('red');
        $this->notificationService->setPriority(2);
        $this->notificationService->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_BUDGET_STATE_CHANGED);
        $this->notificationService->setBroadcastMessage($broadcastMessage);
        $this->notificationService->setBudgetData($budgetData);
        $this->notificationService->setDescription($notificationDescription);
        foreach ($project->access_budget()->get() as $user) {
            $this->notificationService->setNotificationTo($user);
            $this->notificationService->createNotification();
        }

        $this->history->createHistory(
            $request->project_id,
            'Unterposition „' . $subPosition->name . '“ Festschreibung aufgehoben',
            'budget'
        );

        return back()->with('success');
    }

    public function fixMainPosition(Request $request): RedirectResponse
    {
        $mainPosition = MainPosition::find($request->mainPositionId);
        $this->setMainPositionCellVerifiedValue($mainPosition);
        $mainPosition->update(['is_fixed' => true]);
        $this->history->createHistory(
            $request->project_id,
            'Hauptposition „' . $mainPosition->name . '“ festgeschrieben',
            'budget'
        );
        return back()->with('success');
    }

    public function unfixMainPosition(Request $request): RedirectResponse
    {
        $mainPosition = MainPosition::find($request->mainPositionId);
        $this->removeMainPositionCellVerifiedValue($mainPosition);
        $mainPosition->update(['is_fixed' => false]);
        $this->history->createHistory(
            $request->project_id,
            'Hauptposition „' . $mainPosition->name . '“ Festschreibung aufgehoben',
            'budget'
        );
        return back()->with('success');
    }

    public function resetTable(Project $project): RedirectResponse
    {
        $budgetTemplateController = new BudgetTemplateController();
        $budgetTemplateController->deleteOldTable($project);
        //$this->generateBasicBudgetValues($project);
        $this->budgetService->generateBasicBudgetValues($project);

        return back()->with('success');
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
            ->whereJsonContains("data->budgetData->changeType", BudgetTypesEnum::BUDGET_VERIFICATION_REQUEST)
            ->delete();
        $this->history->createHistory(
            $request->project_id,
            'Hauptposition „' . $mainPosition->name . '“ verifiziert',
            'budget'
        );
        return back()->with('success');
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
        MoneySourceThresholdReminderService $moneySourceThresholdReminderService
    ): void {
        ColumnCell::find($request->cell_id)
            ->update([
                'linked_type' => $request->linked_type,
                'linked_money_source_id' => $request->money_source_id
            ]);

        if ($request->money_source_id) {
            $moneySourceThresholdReminderService
                ->handleThresholdReminders(MoneySource::find($request->money_source_id));
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

    public function columnDelete(Column $column): void
    {
        $cells = ColumnCell::where('column_id', $column->id)->get();

        $column->cells()->delete();
        foreach ($cells as $cell) {
            $cell->comments()->delete();
        }
        $column->delete();
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
        $columns = $table->columns()->get();

        $count = 1;

        foreach ($columns as $column) {
            // Skip columns without subname
            if ($column->subName === null || empty($column->subName)) {
                continue;
            }
            $column->update([
                'subName' => $this->getNameFromNumber($count)
            ]);
            $count++;
        }
    }

    public function getNameFromNumber(int $num): string
    {
        $numeric = ($num - 1) % 26;
        $letter = chr(65 + $numeric);
        $num2 = intval(($num - 1) / 26);
        if ($num2 > 0) {
            return $this->getNameFromNumber($num2) . $letter;
        } else {
            return $letter;
        }
    }

    public function addColumn(Request $request): void
    {
        $table = Table::find($request->table_id);
        if ($request->column_type === 'empty') {
            $column = $table->columns()->create([
                'name' => 'empty',
                'subName' => '-',
                'type' => 'empty',
                'linked_first_column' => null,
                'linked_second_column' => null,
            ]);
            $this->setColumnSubName($request->table_id);

            $subPositionRows = SubPositionRow::whereHas(
                'subPosition.mainPosition',
                function (Builder $query) use ($request): void {
                    $query->where('table_id', $request->table_id);
                }
            )->pluck('id');

            foreach ($subPositionRows as $subPositionRow) {
                $column->subPositionRows()->attach($subPositionRow, [
                    'value' => 0,
                    'verified_value' => null,
                    'linked_money_source_id' => null,
                    'commented' => SubPositionRow::find($subPositionRow)->commented
                ]);
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
            ]);
            $this->setColumnSubName($request->table_id);
            foreach ($firstColumns as $firstColumn) {
                $secondColumn = ColumnCell::where('column_id', $request->second_column_id)
                    ->where('sub_position_row_id', $firstColumn->sub_position_row_id)
                    ->first();
                $sum = (int)$firstColumn->value + (int)$secondColumn->value;
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
                'linked_second_column' => $request->second_column_id
            ]);
            $this->setColumnSubName($request->table_id);
            foreach ($firstColumns as $firstColumn) {
                $secondColumn = ColumnCell::where('column_id', $request->second_column_id)
                    ->where('sub_position_row_id', $firstColumn->sub_position_row_id)
                    ->first();
                $sum = (int)$firstColumn->value - (int)$secondColumn->value;
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
        MoneySourceThresholdReminderService $moneySourceThresholdReminderService
    ): void {
        $column = Column::find($request->column_id);
        $project = $column->table()->first()->project()->first();
        $cell = ColumnCell::where('column_id', $request->column_id)
            ->where('sub_position_row_id', $request->sub_position_row_id)
            ->first();

        if ($request->is_verified) {
            $this->history->createHistory(
                $project->id,
                '„' . $cell->value . '“ in „' . $request->value . '“ geändert',
                'budget'
            );
        }

        $cell->update(['value' => $request->value]);
        $this->updateAutomaticCellValues($request->sub_position_row_id);

        if ($cell->linked_money_source_id) {
            $moneySourceThresholdReminderService->handleThresholdReminders(
                MoneySource::find($cell->linked_money_source_id)
            );
        }
    }

    public function changeColumnColor(Request $request): RedirectResponse
    {
        $column = Column::find($request->columnId);
        $column->update(['color' => $request->color]);
        return back()->with('success', 'color changed');
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

        $subPositionRow = $subPosition->subPositionRows()->create([
            'commented' => false,
            'position' => $request->positionBefore + 1
        ]);

        $firstThreeColumns = $columns->shift(3);

        $subPositionRow->columns()->attach($firstThreeColumns->pluck('id'), [
            'value' => '',
            'linked_money_source_id' => null,
            'verified_value' => ''
        ]);

        $subPositionRow->columns()->attach($columns->pluck('id'), [
            'value' => 0,
            'verified_value' => null,
            'linked_money_source_id' => null,
        ]);
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
        $subPositionRow = $subPosition->subPositionRows()->create([
            'commented' => false,
            'position' => 1,
        ]);

        $firstThreeColumns = $columns->shift(3);

        $subPositionRow->columns()->attach($firstThreeColumns->pluck('id'), [
            'value' => '',
            'linked_money_source_id' => null,
            'verified_value' => ''
        ]);

        $subPosition->subPositionSumDetails()->createMany(
            $columns->map(fn($column) => [
                'column_id' => $column->id
            ])->all()
        );

        $subPositionRow->columns()->attach($columns->pluck('id'), [
            'value' => 0,
            'verified_value' => null,
            'linked_money_source_id' => null,
        ]);
    }

    public function updateCellCalculation(Request $request): RedirectResponse
    {
        if ($request->calculations) {
            foreach ($request->calculations as $calculation) {
                $cellCalculation = CellCalculations::find($calculation['id']);
                $cellCalculation->update([
                    'name' => $calculation['name'] ?? '',
                    'value' => $calculation['value'] ?? 0,
                    'description' => $calculation['description'] ?? ''
                ]);
            }

            $cell = ColumnCell::find($request->calculations[0]['cell_id']);
            $cell->update(['value' => $cell->calculations()->sum('value')]);
        }

        return back()->with('success');
    }

    public function addCalculation(ColumnCell $cell, Request $request): void
    {
        // current position found in $request->position
        // add check if $request->position is present, if not set to 0
        if (!$request->position) {
            $request->position = 0;
        }

        $newCalculation = $cell->calculations()->create([
            'name' => '',
            'value' => 0,
            'description' => '',
            'position' => $request->position + 1
        ]);

        // update all positions of calculations where position is greater than $request->position and cell_id is
        // $cell->id and where not id is $newCalculation->id, increment position by 1 after new calculation
        CellCalculations::query()
            ->where('cell_id', $cell->id)
            ->where('position', '>', $request->position)
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

            if ($column->type === 'empty') {
                continue;
            }
            $firstRowValue = ColumnCell::where('column_id', $column->linked_first_column)
                ->where('sub_position_row_id', $subPositionRowId)
                ->first()
                ->value;
            $secondRowValue = ColumnCell::where('column_id', $column->linked_second_column)
                ->where('sub_position_row_id', $subPositionRowId)
                ->first()
                ->value;
            $updateColumn = ColumnCell::where('sub_position_row_id', $subPositionRowId)
                ->where('column_id', $column->id)
                ->first();

            if ($column->type == 'sum') {
                $sum = (int)$firstRowValue + (int)$secondRowValue;
                $updateColumn->update([
                    'value' => $sum
                ]);
            } else {
                $sum = (int)$firstRowValue - (int)$secondRowValue;
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
        return back()->with('success');
    }

    public function unlockColumn(Request $request): RedirectResponse
    {
        $column = Column::find($request->columnId);
        $column->update(['is_locked' => false, 'locked_by' => null]);
        return back()->with('success');
    }

    public function updateProjectState(Request $request, Project $project): void
    {
        $oldState = $project->state()->first();
        $project->update(['state' => $request->state_id]);
        $newState = $project->state()->first();

        if (!empty($newState) && $oldState !== $newState) {
            $this->history->createHistory($project->id, 'Projektstatus hat sich geändert', 'public_changes');
        }
        if (empty($oldState) && !empty($newState)) {
            $this->history->createHistory($project->id, 'Projektstatus hat sich geändert', 'public_changes');
        }
        if (!empty($oldState) && empty($newState)) {
            $this->history->createHistory($project->id, 'Projektstatus hat sich geändert', 'public_changes');
        }

        $this->setPublicChangesNotification($project->id);
    }

    public function projectInfoTab(Project $project)
    {
        $project->load([
            'categories',
            'departments.users.departments',
            'genres',
            'managerUsers',
            'writeUsers',
            'project_files',
            'copyright',
            'cost_center',
            'sectors',
            'users.departments',
            'state',
            'delete_permission_users'
        ]);

        if (!$project->is_group) {
            $group = DB::table('project_groups')
                ->select('*')
                ->where('project_id', '=', $project->id)
                ->first();
            if (!empty($group)) {
                $groupOutput = Project::find($group->group_id);
            } else {
                $groupOutput = '';
            }
        } else {
            $groupOutput = '';
        }

        /** @var Collection $roomsWithAudience */
        $roomsWithAudience = Room::withAudience()->get()->pluck('name', 'id');

        return inertia('Projects/SingleProjectInformation', [
            // needed for the ProjectShowHeaderComponent
            'project' => new ProjectInfoResource($project),
            'firstEventInProject' => $project
                ->events()
                ->orderBy('start_time', 'ASC')
                ->limit(1)
                ->first(),
            'lastEventInProject' => $project
                ->events()
                ->orderBy('end_time', 'DESC')
                ->limit(1)
                ->first(),
            'roomsWithAudience' => $roomsWithAudience->isEmpty() ? null : $roomsWithAudience,
            'projectManagerIds' => $project->managerUsers()->pluck('user_id'),
            'projectWriteIds' => $project->writeUsers()->pluck('user_id'),
            'projectDeleteIds' => $project->delete_permission_users()->pluck('user_id'),
            'eventTypes' => EventTypeResource::collection(EventType::all())->resolve(),
            'currentGroup' => $groupOutput,
            'states' => ProjectStates::all(),
            'projectGroups' => $project->groups()->get(),
            'groupProjects' => Project::where('is_group', 1)->get(),
            // needed for ProjectSecondSidenav
            'categories' => Category::all(),
            'projectCategoryIds' => $project->categories()->pluck('category_id'),
            'projectCategories' => $project->categories,
            'genres' => Genre::all(),
            'projectGenreIds' => $project->genres()->pluck('genre_id'),
            'projectGenres' => $project->genres,
            'sectors' => Sector::all(),
            'projectSectorIds' => $project->sectors()->pluck('sector_id'),
            'projectSectors' => $project->sectors,
            'projectState' => $project->state,
            'access_budget' => $project->access_budget,
        ]);
    }
    public function projectCalendarTab(Project $project, CalendarController $calendar): Response|ResponseFactory
    {
        $showCalendar = $calendar->createCalendarData('', $project);

        $project->load([
            'access_budget',
            'categories',
            'comments.user',
            'departments.users.departments',
            'genres',
            'managerUsers',
            'writeUsers',
            'project_files',
            'contracts',
            'sectors',
            'users.departments',
            'state',
            'delete_permission_users'
        ]);

        if (!$project->is_group) {
            $group = DB::table('project_groups')
                ->select('*')
                ->where('project_id', '=', $project->id)
                ->first();
            if (!empty($group)) {
                $groupOutput = Project::find($group?->group_id);
            } else {
                $groupOutput = '';
            }
        } else {
            $groupOutput = '';
        }

        $eventsAtAGlance = [];

        if (\request('atAGlance') === 'true') {
            $eventsQuery = $project->events();
            $filteredEvents = $calendar->filterEvents($eventsQuery, null, null, null, $project);

            $eventsAtAGlance = ProjectCalendarShowEventResource::collection(
                $filteredEvents
                    ->with(['room','project','creator'])
                    ->orderBy('start_time', 'ASC')
                    ->get()
            )->collection->groupBy('room.id');
        }

        if (\request('startDate') && \request('endDate')) {
            $startDate = Carbon::create(\request('startDate'))->startOfDay();
            $endDate = Carbon::create(\request('endDate'))->endOfDay();
        } else {
            $startDate = Carbon::now()->startOfDay();
            $endDate = Carbon::now()->addWeeks()->endOfDay();
        }

        /** @var Collection $roomsWithAudience */
        $roomsWithAudience = Room::withAudience()->get()->pluck('name', 'id');

        return inertia('Projects/SingleProjectCalendar', [
            // needed for the ProjectShowHeaderComponent
            'project' => new ProjectCalendarResource($project),
            'firstEventInProject' => $project->events()->orderBy('start_time', 'ASC')->limit(1)->first(),
            'lastEventInProject' => $project->events()->orderBy('end_time', 'DESC')->limit(1)->first(),
            'roomsWithAudience' => $roomsWithAudience->isEmpty() ? null : $roomsWithAudience,
            'projectManagerIds' => $project->managerUsers()->pluck('user_id'),
            'projectWriteIds' => $project->writeUsers()->pluck('user_id'),
            'projectDeleteIds' => $project->delete_permission_users()->pluck('user_id'),
            'eventTypes' => EventTypeResource::collection(EventType::all())->resolve(),
            'currentGroup' => $groupOutput,
            'states' => ProjectStates::all(),
            'projectGroups' => $project->groups()->get(),
            'groupProjects' => Project::where('is_group', 1)->get(),
            // needed for ProjectSecondSidenav
            'categories' => Category::all(),
            'projectCategoryIds' => $project->categories()->pluck('category_id'),
            'projectCategories' => $project->categories,
            'genres' => Genre::all(),
            'projectGenreIds' => $project->genres()->pluck('genre_id'),
            'projectGenres' => $project->genres,
            'sectors' => Sector::all(),
            'projectSectorIds' => $project->sectors()->pluck('sector_id'),
            'projectSectors' => $project->sectors,
            'projectState' => $project->state,
            // needed for SingleProjectCalendar
            'eventsAtAGlance' => $eventsAtAGlance,
            'calendar' => $showCalendar['roomsWithEvents'],
            'dateValue' => $showCalendar['dateValue'],
            'days' => $showCalendar['days'],
            'selectedDate' => $showCalendar['selectedDate'],
            'rooms' => $calendar->filterRooms($startDate, $endDate)->get(),
            'events' => new CalendarEventCollectionResourceModel(
                areas: $showCalendar['filterOptions']['areas'],
                projects: $showCalendar['filterOptions']['projects'],
                eventTypes: $showCalendar['filterOptions']['eventTypes'],
                roomCategories: $showCalendar['filterOptions']['roomCategories'],
                roomAttributes: $showCalendar['filterOptions']['roomAttributes'],
                events: $calendar->getEventsOfDay($project),
                filter: Filter::query()->where('user_id', Auth::id())->get(),
            ),
            'filterOptions' => $showCalendar["filterOptions"],
            'personalFilters' => $showCalendar['personalFilters'],
            'eventsWithoutRoom' => $showCalendar['eventsWithoutRoom'],
            'user_filters' => $showCalendar['user_filters'],
            'access_budget' => $project->access_budget,
        ]);
    }

    public function projectChecklistTab(Project $project): Response|ResponseFactory
    {
        $project->load([
            'categories',
            'checklists.users',
            'checklists.tasks.checklist.project',
            'checklists.tasks.user_who_done',
            'departments.users.departments',
            'genres',
            'managerUsers',
            'writeUsers',
            'sectors',
            'users.departments',
            'state',
            'delete_permission_users'
        ]);

        if (!$project->is_group) {
            $group = DB::table('project_groups')
                ->select('*')
                ->where('project_id', '=', $project->id)
                ->first();
            if (!empty($group)) {
                $groupOutput = Project::find($group?->group_id);
            } else {
                $groupOutput = '';
            }
        } else {
            $groupOutput = '';
        }

        /** @var Collection $roomsWithAudience */
        $roomsWithAudience = Room::withAudience()->get()->pluck('name', 'id');

        return inertia('Projects/SingleProjectChecklists', [
            'project' => new ProjectChecklistResource($project),
            'firstEventInProject' => $project->events()->orderBy('start_time', 'ASC')->limit(1)->first(),
            'lastEventInProject' => $project->events()->orderBy('end_time', 'DESC')->limit(1)->first(),
            'roomsWithAudience' => $roomsWithAudience->isEmpty() ? null : $roomsWithAudience,
            'projectManagerIds' => $project->managerUsers()->pluck('user_id'),
            'projectWriteIds' => $project->writeUsers()->pluck('user_id'),
            'projectDeleteIds' => $project->delete_permission_users()->pluck('user_id'),
            'eventTypes' => EventTypeResource::collection(EventType::all())->resolve(),
            'project_id' => $project->id,
            'opened_checklists' => User::where('id', Auth::id())->first()->opened_checklists,
            'states' => ProjectStates::all(),
            'groupProjects' => Project::where('is_group', 1)->get(),
            'projectGroups' => $project->groups()->get(),
            'currentGroup' => $groupOutput,
            'checklist_templates' => ChecklistTemplateIndexResource::collection(ChecklistTemplate::all())->resolve(),
            'access_budget' => $project->access_budget,
        ]);
    }

    //@todo: fix phpcs error - refactor function because complexity is rising
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
    public function projectShiftTab(Project $project): Response|ResponseFactory
    {
        $project->load([
            'departments.users.departments',
            'managerUsers',
            'writeUsers',
            'project_files',
            'sectors',
            'users.departments',
            'state',
            'delete_permission_users'
        ]);

        if (!$project->is_group) {
            $group = DB::table('project_groups')
                ->select('*')
                ->where('project_id', '=', $project->id)
                ->first();
            if (!empty($group)) {
                $groupOutput = Project::find($group?->group_id);
            } else {
                $groupOutput = '';
            }
        } else {
            $groupOutput = '';
        }

        $shiftRelevantEventTypes = $project->shiftRelevantEventTypes()->pluck('event_type_id');
        $shiftRelevantEvents = $project->events()
            ->whereIn('event_type_id', $shiftRelevantEventTypes)
            ->with(['timeline', 'shifts', 'event_type', 'room'])
            ->get();

        $eventsWithRelevant = [];
        foreach ($shiftRelevantEvents as $event) {
            $timeline = $event->timeline()->get()->toArray();

            foreach ($timeline as &$singleTimeLine) {
                $singleTimeLine['description_without_html'] = strip_tags($singleTimeLine['description']);
            }

            usort($timeline, function ($a, $b) {
                if ($a['start'] === null && $b['start'] === null) {
                    return 0;
                } elseif ($a['start'] === null) {
                    return 1; // $a should come later in the array
                } elseif ($b['start'] === null) {
                    return -1; // $b should come later in the array
                }

                // Compare the 'start' values for ascending order
                return strtotime($a['start']) - strtotime($b['start']);
            });
            $eventsWithRelevant[$event->id] = [
                'event' => $event,
                'timeline' => $timeline,
                'shifts' => $event->shifts,
                'event_type' => $event->event_type,
                'room' => $event->room,
            ];
        }
        rsort($eventsWithRelevant);

        $firstEventInProject = $project->events()->orderBy('start_time', 'ASC')->limit(1)->first();
        $lastEventInProject = $project->events()->orderBy('end_time', 'DESC')->limit(1)->first();
        if ($firstEventInProject && $lastEventInProject) {
            //get the start of day of the firstEventInProject
            $startDate = Carbon::create($firstEventInProject->start_time)->startOfDay();
            //get the end of day of the lastEventInProject
            $endDate = Carbon::create($lastEventInProject->end_time)->endOfDay();
        } else {
            $startDate = Carbon::now()->startOfDay();
            $endDate = Carbon::now()->addWeeks()->endOfDay();
        }
        //get the diff of startDate and endDate in days, +1 to include the current date
        $diffInDays = $startDate->diffInDays($endDate) + 1;

        $usersWithPlannedWorkingHours = [];
        foreach (User::query()->where('can_work_shifts', true)->get() as $user) {
            $usersWithPlannedWorkingHours[] = [
                'user' => UserDropResource::make($user),
                'plannedWorkingHours' => $user->plannedWorkingHours($startDate, $endDate),
                'expectedWorkingHours' => ($user->weekly_working_hours / 7) * $diffInDays,
                'vacations' => $user->hasVacationDays(),
            ];
        }

        $freelancersWithPlannedWorkingHours = [];
        foreach (Freelancer::query()->where('can_work_shifts', true)->get() as $freelancer) {
            $freelancersWithPlannedWorkingHours[] = [
                'freelancer' => FreelancerDropResource::make($freelancer),
                'plannedWorkingHours' => $freelancer->plannedWorkingHours($startDate, $endDate),
            ];
        }

        $serviceProvidersWithPlannedWorkingHours = [];
        foreach (
            ServiceProvider::query()
                ->where('can_work_shifts', true)
                ->without(['contacts'])
                ->get() as $service_provider
        ) {
            $serviceProvidersWithPlannedWorkingHours[] = [
                'service_provider' => ServiceProviderDropResource::make($service_provider),
                'plannedWorkingHours' => $service_provider->plannedWorkingHours($startDate, $endDate),
            ];
        }

        /** @var Collection $roomsWithAudience */
        $roomsWithAudience = Room::withAudience()->get()->pluck('name', 'id');

        return inertia('Projects/SingleProjectShifts', [
            'project' => new ProjectShiftResource($project),
            'usersForShifts' => $usersWithPlannedWorkingHours,
            'freelancersForShifts' => $freelancersWithPlannedWorkingHours,
            'serviceProvidersForShifts' => $serviceProvidersWithPlannedWorkingHours,
            'firstEventInProject' => $firstEventInProject,
            'lastEventInProject' => $lastEventInProject,
            'roomsWithAudience' => $roomsWithAudience->isEmpty() ? null : $roomsWithAudience,
            'projectManagerIds' => $project->managerUsers()->pluck('user_id'),
            'projectWriteIds' => $project->writeUsers()->pluck('user_id'),
            'projectDeleteIds' => $project->delete_permission_users()->pluck('user_id'),
            'groupProjects' => Project::where('is_group', 1)->get(),
            'projectGroups' => $project->groups()->get(),
            'currentGroup' => $groupOutput,
            'projectState' => $project->state,
            'eventTypes' => EventTypeResource::collection(EventType::all())->resolve(),
            'states' => ProjectStates::all(),
            'eventsWithRelevant' => $eventsWithRelevant,
            'crafts' => Craft::all(),
            'access_budget' => $project->access_budget,
            'currentUserCrafts' => Auth::user()
                ->crafts
                ->merge(Craft::query()->where('assignable_by_all', '=', true)->get())
        ]);
    }

    //@todo: fix phpcs error - refactor function because complexity is rising
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
    public function projectBudgetTab(Project $project): Response|ResponseFactory
    {
        $project->load([
            'access_budget',
            'departments.users.departments',
            'managerUsers',
            'writeUsers',
            'project_files',
            'contracts',
            'copyright',
            'cost_center',
            'users.departments',
            'state',
            'delete_permission_users'
        ]);

        $columns = $project->table()->first()->columns()->get();

        $calculateNames = [];
        foreach ($columns as $column) {
            $calculateName = '';
            if ($column->type === 'difference' || $column->type === 'sum') {
                $firstName = Column::where('id', $column->linked_first_column)->first()?->subName;
                $secondName = Column::where('id', $column->linked_second_column)->first()?->subName;
                $calculateName = $firstName . ' + ' . $secondName;
            }
            $calculateNames[$column->id] = $calculateName;
        }

        if (!$project->is_group) {
            $group = DB::table('project_groups')->select('*')->where('project_id', '=', $project->id)->first();
            if (!empty($group)) {
                $groupOutput = Project::find($group?->group_id);
            } else {
                $groupOutput = '';
            }
        } else {
            $groupOutput = '';
        }

        $selectedCell = request('selectedCell')
            ? ColumnCell::find(request('selectedCell'))
            : null;

        $selectedRow = request('selectedRow')
            ? SubPositionRow::find(request('selectedRow'))
            : null;

        $templates = Table::where('is_template', true)->get();

        $selectedSumDetail = null;

        if (request('selectedSubPosition') && request('selectedColumn')) {
            $selectedSumDetail = Collection::make(
                SubpositionSumDetail::with(['comments.user', 'sumMoneySource.moneySource'])
                    ->where('sub_position_id', request('selectedSubPosition'))
                    ->where('column_id', request('selectedColumn'))
                    ->first()
            )->merge(['class' => SubpositionSumDetail::class]);
        }

        if (request('selectedMainPosition') && request('selectedColumn')) {
            $selectedSumDetail = Collection::make(
                MainPositionDetails::with(['comments.user', 'sumMoneySource.moneySource'])
                    ->where('main_position_id', request('selectedMainPosition'))
                    ->where('column_id', request('selectedColumn'))
                    ->first()
            )->merge(['class' => MainPositionDetails::class]);
        }

        if (request('selectedBudgetType') && request('selectedColumn')) {
            $selectedSumDetail = Collection::make(
                BudgetSumDetails::with(['comments.user', 'sumMoneySource.moneySource'])
                    ->where('type', request('selectedBudgetType'))
                    ->where('column_id', request('selectedColumn'))
                    ->first()
            )->merge(['class' => BudgetSumDetails::class]);
        }

        //load commented budget items setting for given user
        Auth::user()->load(['commentedBudgetItemsSetting']);

        /** @var Collection $roomsWithAudience */
        $roomsWithAudience = Room::withAudience()->get()->pluck('name', 'id');

        return inertia('Projects/SingleProjectBudget', [
            'project' => new ProjectBudgetResource($project),
            'firstEventInProject' => $project->events()->orderBy('start_time', 'ASC')->limit(1)->first(),
            'lastEventInProject' => $project->events()->orderBy('end_time', 'DESC')->limit(1)->first(),
            'roomsWithAudience' => $roomsWithAudience->isEmpty() ? null : $roomsWithAudience,
            'projectManagerIds' => $project->managerUsers()->pluck('user_id'),
            'projectWriteIds' => $project->writeUsers()->pluck('user_id'),
            'projectDeleteIds' => $project->delete_permission_users()->pluck('user_id'),
            'moneySources' => MoneySource::all(),
            'budget' => [
                'table' => $project->table()
                    ->with([
                        'columns',
                        'mainPositions',
                        'mainPositions.verified',
                        'mainPositions.subPositions' => function ($query) {
                            return $query->orderBy('position');
                        },
                        'mainPositions.subPositions.verified',
                        'mainPositions.subPositions.subPositionRows' => function ($query) {
                            return $query->orderBy('position');
                        }, 'mainPositions.subPositions.subPositionRows.cells' => function ($query): void {
                            $query->withCount('comments')
                                ->withCount(['calculations' => function ($query) {
                                    // count if value is not 0
                                    return $query->where('value', '!=', 0);
                                }]);
                        }, 'mainPositions.subPositions.subPositionRows.cells.column'
                    ])
                    ->first(),
                'selectedCell' => $selectedCell?->load(['calculations' => function ($calculations): void {
                    $calculations->orderBy('position', 'asc');
                }, 'comments.user', 'comments', 'column' => function ($query): void {
                    $query->orderBy('created_at', 'desc');
                }]),
                'selectedSumDetail' => $selectedSumDetail,
                'selectedRow' => $selectedRow?->load(['comments.user', 'comments' => function ($query): void {
                    $query->orderBy('created_at', 'desc');
                }]),
                'templates' => $templates,
                'columnCalculatedNames' => $calculateNames,
            ],
            'projectGroups' => $project->groups()->get(),
            'groupProjects' => Project::where('is_group', 1)->get(),
            'currentGroup' => $groupOutput,
            'projectState' => $project->state,
            'access_budget' => $project->access_budget,
            'eventTypes' => EventTypeResource::collection(EventType::all())->resolve(),
            'projectMoneySources' => $project->moneySources()->get(),
            'states' => ProjectStates::all(),
        ]);
    }

    public function projectCommentTab(Project $project): Response|ResponseFactory
    {
        $project->load([
            'categories',
            'comments.user',
            'departments.users.departments',
            'genres',
            'managerUsers',
            'writeUsers',
            'project_files',
            'sectors',
            'users.departments',
            'state',
            'delete_permission_users'
        ]);

        if (!$project->is_group) {
            $group = DB::table('project_groups')->select('*')->where('project_id', '=', $project->id)->first();
            if (!empty($group)) {
                $groupOutput = Project::find($group?->group_id);
            } else {
                $groupOutput = '';
            }
        } else {
            $groupOutput = '';
        }

        /** @var Collection $roomsWithAudience */
        $roomsWithAudience = Room::withAudience()->get()->pluck('name', 'id');

        return inertia('Projects/SingleProjectComments', [
            'project' => new ProjectCommentResource($project),
            'firstEventInProject' => $project->events()->orderBy('start_time', 'ASC')->limit(1)->first(),
            'lastEventInProject' => $project->events()->orderBy('end_time', 'DESC')->limit(1)->first(),
            'roomsWithAudience' => $roomsWithAudience->isEmpty() ? null : $roomsWithAudience,
            'projectManagerIds' => $project->managerUsers()->pluck('user_id'),
            'projectWriteIds' => $project->writeUsers()->pluck('user_id'),
            'projectDeleteIds' => $project->delete_permission_users()->pluck('user_id'),
            'categories' => Category::all(),
            'projectCategoryIds' => $project->categories()->pluck('category_id'),
            'projectCategories' => $project->categories,
            'groupProjects' => Project::where('is_group', 1)->get(),
            'projectGroups' => $project->groups()->get(),
            'currentGroup' => $groupOutput,
            'genres' => Genre::all(),
            'projectGenreIds' => $project->genres()->pluck('genre_id'),
            'projectGenres' => $project->genres,
            'sectors' => Sector::all(),
            'projectSectorIds' => $project->sectors()->pluck('sector_id'),
            'projectSectors' => $project->sectors,
            'projectState' => $project->state,
            'eventTypes' => EventTypeResource::collection(EventType::all())->resolve(),
            'states' => ProjectStates::all(),
            'access_budget' => $project->access_budget,
        ]);
    }

    public function addTimeLineRow(Event $event, Request $request): void
    {
        $event->timeline()->create(
            $request->validate(
                [
                    'start' => 'required',
                    'end' => 'required',
                    'description' => 'nullable'
                ]
            )
        );
    }

    public function updateTimeLines(Request $request): void
    {
        foreach ($request->timelines as $timeline) {
            $findTimeLine = TimeLine::find($timeline['id']);
            $findTimeLine->update([
                'start' => $timeline['start'],
                'end' => $timeline['end'],
                'description' => nl2br($timeline['description_without_html'])
            ]);
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
        $update_properties = $request->only('name', 'budget_deadline');

        if ($request->selectedGroup === null) {
            DB::table('project_groups')->where('project_id', '=', $project->id)->delete();
        } else {
            DB::table('project_groups')->where('project_id', '=', $project->id)->delete();
            $group = Project::find($request->selectedGroup['id']);
            $group->groups()->syncWithoutDetaching($project->id);
        }

        $oldProjectName = $project->name;
        $oldProjectBudgetDeadline = $project->budget_deadline;

        $project->fill($update_properties);
        $project->save();

        $newProjectName = $project->name;
        $newProjectBudgetDeadline = $project->budget_deadline;

        // history functions
        $this->checkProjectNameChanges($project->id, $oldProjectName, $newProjectName);
        $this->checkProjectBudgetDeadlineChanges($project->id, $oldProjectBudgetDeadline, $newProjectBudgetDeadline);

        $projectId = $project->id;
        foreach ($project->users->all() as $user) {
            $this->schedulingController->create($user->id, 'PROJECT_CHANGES', 'PROJECTS', $projectId);
        }
        return Redirect::back();
    }

    public function updateTeam(Request $request, Project $project): JsonResponse|RedirectResponse
    {

        if (!Auth::user()->hasRole(RoleNameEnum::ARTWORK_ADMIN->value)) {
            // authorization
            if (
                !Auth::user()->canAny([
                    PermissionNameEnum::PROJECT_MANAGEMENT->value,
                    PermissionNameEnum::ADD_EDIT_OWN_PROJECT->value,
                    PermissionNameEnum::WRITE_PROJECTS->value
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
        $oldProjectGenres = $project->genres()->get();
        $oldProjectSectors = $project->sectors()->get();

        $project->categories()->sync($request->assignedCategoryIds);
        $project->genres()->sync($request->assignedGenreIds);
        $project->sectors()->sync($request->assignedSectorIds);

        $newProjectGenres = $project->genres()->get();
        $newProjectSectors = $project->sectors()->get();
        $newProjectCategories = $project->sectors()->get();

        // history functions
        $this->checkProjectCategoryChanges($project->id, $oldProjectCategories, $newProjectCategories);
        $this->checkProjectGenreChanges($project->id, $oldProjectGenres, $newProjectGenres);
        $this->checkProjectSectorChanges($project->id, $oldProjectSectors, $newProjectSectors);

        return Redirect::back();
    }

    public function updateDescription(Request $request, Project $project): JsonResponse|RedirectResponse
    {
        $oldDescription = $project->description;
        $update_properties = $request->only('description');
        $project->fill($update_properties);

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
                $this->history->createHistory(
                    $projectId,
                    'Bereich ' . $newSector->name . ' hinzugefügt',
                    'public_changes'
                );
            }
        }

        foreach ($oldSectorIds as $oldSectorId) {
            if (!in_array($oldSectorId, $newSectorIds)) {
                $this->history->createHistory(
                    $projectId,
                    'Bereich ' . $oldSectorNames[$oldSectorId] . ' gelöscht',
                    'public_changes'
                );
            }
        }

        $this->setPublicChangesNotification($projectId);
    }

    public function deleteProjectFromGroup(Request $request): void
    {
        $group = Project::find($request->groupId);
        $group->groups()->detach($request->projectIdToDelete);
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
                $this->history->createHistory(
                    $projectId,
                    'Genre ' . $newGenre->name . ' hinzugefügt',
                    'public_changes'
                );
            }
        }

        foreach ($oldGenreIds as $oldGenreId) {
            if (!in_array($oldGenreId, $newGenreIds)) {
                $this->history->createHistory(
                    $projectId,
                    'Genre ' . $oldGenreNames[$oldGenreId] . ' gelöscht',
                    'public_changes'
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
                $this->history->createHistory(
                    $projectId,
                    'Kategorie ' . $newCategory->name . ' hinzugefügt',
                    'public_changes'
                );
            }
        }

        foreach ($oldCategoryIds as $oldCategoryId) {
            if (!in_array($oldCategoryId, $newCategoryIds)) {
                $this->history->createHistory(
                    $projectId,
                    'Kategorie ' . $oldCategoryNames[$oldCategoryId] . ' gelöscht',
                    'public_changes'
                );
            }
        }

        $this->setPublicChangesNotification($projectId);
    }

    private function checkProjectNameChanges($projectId, $oldName, $newName): void
    {
        if ($oldName !== $newName) {
            $this->history->createHistory($projectId, 'Projektname geändert', 'public_changes');
            $this->setPublicChangesNotification($projectId);
        }
    }

    private function checkProjectBudgetDeadlineChanges(
        int $projectId,
        string|null $oldProjectBudgetDeadline,
        string $newProjectBudgetDeadline
    ): void {
        if ($oldProjectBudgetDeadline !== $newProjectBudgetDeadline) {
            $this->history->createHistory($projectId, 'Projekt Stichtag Budget geändert', 'public_changes');
            $this->setPublicChangesNotification($projectId);
        }
    }

    public function setPublicChangesNotification($projectId): void
    {
        $project = Project::find($projectId);
        $projectUsers = $project->users()->get();
        foreach ($projectUsers as $projectUser) {
            $this->schedulingController->create($projectUser->id, 'PUBLIC_CHANGES', 'PROJECTS', $project->id);
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
                $this->history->createHistory($projectId, 'Projektteam ' . $newDepartment->name . ' hinzugefügt');
            }
        }

        foreach ($oldDepartmentIds as $oldDepartmentId) {
            if (!in_array($oldDepartmentId, $newDepartmentIds)) {
                $this->history->createHistory(
                    $projectId,
                    'Projektteam ' . $oldDepartmentNames[$oldDepartmentId] . ' entfernt'
                );
            }
        }
    }

    private function checkProjectDescriptionChanges($projectId, $oldDescription, $newDescription): void
    {
        if (strlen($newDescription) === null) {
            $this->history->createHistory($projectId, 'Kurzbeschreibung gelöscht', 'public_changes');
        }
        if ($oldDescription === null && $newDescription !== null) {
            $this->history->createHistory($projectId, 'Kurzbeschreibung hinzugefügt', 'public_changes');
        }
        if ($oldDescription !== $newDescription && $oldDescription !== null && strlen($newDescription) !== null) {
            $this->history->createHistory($projectId, 'Kurzbeschreibung geändert', 'public_changes');
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
                $notificationTitle = 'Du wurdest zur Projektleitung von ' . $project->name . ' ernannt';
                $broadcastMessage = [
                    'id' => rand(1, 1000000),
                    'type' => 'success',
                    'message' => $notificationTitle
                ];
                $this->notificationService->setTitle($notificationTitle);
                $this->notificationService->setIcon('green');
                $this->notificationService->setPriority(3);
                $this->notificationService->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_PROJECT);
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
                $notificationTitle = 'Du hast Budgetzugriff in ' . $project->name . ' erhalten';
                $broadcastMessage = [
                    'id' => rand(1, 1000000),
                    'type' => 'success',
                    'message' => $notificationTitle
                ];
                $this->notificationService->setTitle($notificationTitle);
                $this->notificationService->setIcon('green');
                $this->notificationService->setPriority(3);
                $this->notificationService->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_PROJECT);
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
                $notificationTitle = 'Du wurdest als Projektleitung von ' . $project->name . ' gelöscht';
                $broadcastMessage = [
                    'id' => rand(1, 1000000),
                    'type' => 'error',
                    'message' => $notificationTitle
                ];
                $this->notificationService->setTitle($notificationTitle);
                $this->notificationService->setIcon('red');
                $this->notificationService->setPriority(2);
                $this->notificationService->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_PROJECT);
                $this->notificationService->setBroadcastMessage($broadcastMessage);
                $this->notificationService->setProjectId($project->id);
                $this->notificationService->setNotificationTo($user);
                $this->notificationService->createNotification();
            }
        }
        foreach ($budgetIdsBefore as $budgetBefore) {
            if (!in_array($budgetBefore, $budgetIdsAfter)) {
                $user = User::find($budgetBefore);
                $notificationTitle = 'Dein Budgetzugriff in ' . $project->name . ' wurde gelöscht';
                $broadcastMessage = [
                    'id' => rand(1, 1000000),
                    'type' => 'error',
                    'message' => $notificationTitle
                ];
                $this->notificationService->setTitle($notificationTitle);
                $this->notificationService->setIcon('red');
                $this->notificationService->setPriority(2);
                $this->notificationService->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_PROJECT);
                $this->notificationService->setBroadcastMessage($broadcastMessage);
                $this->notificationService->setProjectId($project->id);
                $this->notificationService->setNotificationTo($user);
                $this->notificationService->createNotification();
            }
        }
        foreach ($userIdsAfter as $userIdAfter) {
            if (!in_array($userIdAfter, $userIdsBefore)) {
                $user = User::find($userIdAfter);
                $notificationTitle = 'Du wurdest zu ' . $project->name . ' hinzugefügt';
                $broadcastMessage = [
                    'id' => rand(1, 1000000),
                    'type' => 'success',
                    'message' => $notificationTitle
                ];
                $this->notificationService->setTitle($notificationTitle);
                $this->notificationService->setIcon('green');
                $this->notificationService->setPriority(3);
                $this->notificationService->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_PROJECT);
                $this->notificationService->setBroadcastMessage($broadcastMessage);
                $this->notificationService->setProjectId($project->id);
                $this->notificationService->setNotificationTo($user);
                $this->notificationService->createNotification();
            }
        }
        foreach ($userIdsBefore as $userIdBefore) {
            if (!in_array($userIdBefore, $userIdsAfter)) {
                $user = User::find($userIdBefore);
                $notificationTitle = 'Du wurdest aus ' . $project->name . ' gelöscht';
                $broadcastMessage = [
                    'id' => rand(1, 1000000),
                    'type' => 'success',
                    'message' => $notificationTitle
                ];
                $this->notificationService->setTitle($notificationTitle);
                $this->notificationService->setIcon('red');
                $this->notificationService->setPriority(2);
                $this->notificationService->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_PROJECT);
                $this->notificationService->setBroadcastMessage($broadcastMessage);
                $this->notificationService->setProjectId($project->id);
                $this->notificationService->setNotificationTo($user);
                $this->notificationService->createNotification();
            }
        }
    }

    public function duplicate(Project $project, HistoryService $historyService)
    {
        // authorization
        if ($project->users->isNotEmpty() || !Auth::user()->hasRole(RoleNameEnum::ARTWORK_ADMIN->value)) {
            if (
                !Auth::user()->canAny([
                    PermissionNameEnum::PROJECT_MANAGEMENT->value,
                    PermissionNameEnum::ADD_EDIT_OWN_PROJECT->value,
                    PermissionNameEnum::WRITE_PROJECTS->value
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
        ]);
        $historyService->projectUpdated($newProject);

        //$this->generateBasicBudgetValues($newProject);

        $this->budgetService->generateBasicBudgetValues($newProject);

        $newProject->users()->attach([Auth::id() => ['access_budget' => true]]);
        $newProject->categories()->sync($project->categories->pluck('id'));
        $newProject->sectors()->sync($project->sectors->pluck('id'));
        $newProject->genres()->sync($project->genres->pluck('id'));
        $newProject->departments()->sync($project->departments->pluck('id'));
        $newProject->users()->sync($project->users->pluck('id'));

        $historyService->updateHistory($project, config('history.project.duplicated'));

        return Redirect::route('projects.show.info', $newProject->id)->with('success', 'Project created.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        $project->events()->delete();

        foreach ($project->checklists() as $checklist) {
            $checklist->tasks()->delete();
        }

        $notificationTitle = $project->name . ' wurde gelöscht';
        $broadcastMessage = [
            'id' => rand(1, 1000000),
            'type' => 'error',
            'message' => $notificationTitle
        ];

        $this->notificationService->setTitle($notificationTitle);
        $this->notificationService->setIcon('red');
        $this->notificationService->setPriority(2);
        $this->notificationService->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_PROJECT);
        $this->notificationService->setBroadcastMessage($broadcastMessage);
        $this->notificationService->setProjectId($project->id);

        foreach ($project->users()->get() as $user) {
            $this->notificationService->setNotificationTo($user);
            $this->notificationService->createNotification();
        }

        $project->checklists()->delete();

        $project->delete();

        return Redirect::route('projects')->with('success', 'Project moved to trash');
    }

    public function forceDelete(int $id): RedirectResponse
    {
        /** @var Project $project */
        $project = Project::onlyTrashed()->findOrFail($id);

        $project->forceDelete();
        $project->events()->withTrashed()->forceDelete();
        $project->project_histories()->delete();

        return Redirect::route('projects.trashed')->with('success', 'Project deleted');
    }

    public function restore(int $id): RedirectResponse
    {
        $project = Project::onlyTrashed()->findOrFail($id);

        $project->restore();
        $project->events()->withTrashed()->restore();

        return Redirect::route('projects.trashed')->with('success', 'Project restored');
    }

    public function getTrashedSettings(): Response|ResponseFactory
    {
        return inertia('Trash/ProjectSettings', [
            'trashed_genres' => Genre::onlyTrashed()->get(),
            'trashed_categories' => Category::onlyTrashed()->get(),
            'trashed_sectors' => Sector::onlyTrashed()->get(),
            'trashed_project_states' => ProjectStates::onlyTrashed()->get(),
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

    public function deleteRow(SubPositionRow $row): void
    {
        $row->forceDelete();
    }

    public function deleteTable(Table $table): void
    {
        $table->forceDelete();
    }

    public function deleteMainPosition(MainPosition $mainPosition): void
    {
        $subPositions = $mainPosition->subPositions()->get();
        foreach ($subPositions as $subPosition) {
            $subRows = $subPosition->subPositionRows()->get();

            foreach ($subRows as $subRow) {
                $cells = $subRow->cells()->get();
                foreach ($cells as $cell) {
                    $cell->delete();
                }
                $subRow->delete();
            }
            $subPosition->delete();
        }
        $mainPosition->delete();
    }

    public function deleteSubPosition(SubPosition $subPosition): void
    {
        $subRows = $subPosition->subPositionRows()->get();

        foreach ($subRows as $subRow) {
            $cells = $subRow->cells()->get();
            foreach ($cells as $cell) {
                $cell->delete();
            }
            $subRow->delete();
        }
        $subPosition->delete();
    }

    public function updateCommentedStatusOfRow(Request $request, SubPositionRow $row): RedirectResponse
    {
        $row->update(['commented' => $request->commented]);

        $cellIds = $row->cells->skip(3)->pluck('id');

        $row->cells()->whereIntegerInRaw('id', $cellIds)->update(['commented' => $request->commented]);

        return back()->with('success');
    }

    public function updateCommentedStatusOfCell(Request $request, ColumnCell $columnCell): RedirectResponse
    {
        $columnCell->update(['commented' => $request->commented]);
        return back()->with('success');
    }

    public function updateKeyVisual(Request $request, Project $project): RedirectResponse
    {
        $oldKeyVisual = $project->key_visual_path;
        if ($request->file('keyVisual')) {
            $file = $request->file('keyVisual');

            $img = Image::make($file);

            $height = $img->height();
            $width = $img->width();
            $ratio = $width / $height;

            if ($ratio < 4 || $ratio > 8) {
                throw ValidationException::withMessages([
                    'key_visual' => 'Das Key Visual sollte mindestens 4 und maximal 8 mal so breit wie hoch sein. ' .
                        'Im Idealfall 1150px breit und 200px hoch.'
                ]);
            }

            Storage::delete('keyVisual/' . $project->key_visual_path);

            $original_name = $file->getClientOriginalName();
            $basename = Str::random(20) . $original_name;

            $project->key_visual_path = $basename;
            $img->save(Storage::path('public/keyVisual') . '/header_' . $basename, 100, $file->clientExtension());
            Storage::putFileAs('public/keyVisual', $file, $basename);
        }
        $project->save();

        $newKeyVisual = $project->key_visual_path;

        if ($oldKeyVisual !== $newKeyVisual) {
            $this->history->createHistory($project->id, 'Key Visual wurde geändert', 'public_changes');
        }

        if ($newKeyVisual === '') {
            $this->history->createHistory($project->id, 'Key Visual wurde entfernt', 'public_changes');
        }

        $this->setPublicChangesNotification($project->id);

        return Redirect::back()->with('success', 'Key Visual hinzugefügt');
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

    public function deleteTimeLineRow(TimeLine $timeLine): void
    {
        $timeLine->delete();
    }

    public function duplicateColumn(Column $column): void
    {
        $newColumn = $column->replicate();
        $newColumn->save();
        $newColumn->update(['name' => $column->name . ' (Kopie)']);
        $newColumn->cells()->delete();
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
            $newSubPositionRow->cells()->delete();
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
        return (new ProjectBudgetExport($project))
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
        string $endBudgetDeadline
    ): BinaryFileResponse {
        return (new ProjectBudgetsByBudgetDeadlineExport($startBudgetDeadline, $endBudgetDeadline))
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

    public function pin(Project $project): RedirectResponse
    {
        $this->projectService->pin($project);
        return Redirect::route('projects')->with('success', 'Project pinned.');
    }
}
