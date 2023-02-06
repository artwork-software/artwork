<?php

namespace App\Http\Controllers;

use Antonrom\ModelChangesHistory\Models\Change;
use App\Enums\BudgetTypesEnum;
use App\Enums\NotificationConstEnum;
use App\Enums\PermissionNameEnum;
use App\Enums\RoleNameEnum;
use App\Http\Requests\SearchRequest;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Resources\BudgetResource;
use App\Http\Resources\EventTypeResource;
use App\Http\Resources\ProjectEditResource;
use App\Http\Resources\ProjectIndexResource;
use App\Http\Resources\ProjectShowResource;
use App\Http\Resources\UserIndexResource;
use App\Models\Category;
use App\Models\CellCalculations;
use App\Models\CellComment;
use App\Models\Checklist;
use App\Models\ChecklistTemplate;
use App\Models\Column;
use App\Models\ColumnCell;
use App\Models\Department;
use App\Models\EventType;
use App\Models\Genre;
use App\Models\MainPosition;
use App\Models\MoneySource;
use App\Models\Project;
use App\Models\ProjectGroups;
use App\Models\Sector;
use App\Models\SubPosition;
use App\Models\SubPositionRow;
use App\Models\Table;
use App\Models\Task;
use App\Models\User;
use App\Support\Services\HistoryService;
use Barryvdh\Debugbar\Facades\Debugbar;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Inertia\Response;
use Inertia\ResponseFactory;
use stdClass;
use function Clue\StreamFilter\fun;
use function Pest\Laravel\get;


class ProjectController extends Controller
{
    // init empty notification controller
    protected ?NotificationController $notificationController = null;
    protected ?stdClass $notificationData = null;
    protected ?HistoryController $history = null;

    public function __construct()
    {

        // init notification controller
        $this->notificationController = new NotificationController();
        $this->notificationData = new stdClass();
        $this->notificationData->project = new stdClass();
        $this->notificationData->type = NotificationConstEnum::NOTIFICATION_PROJECT;
        $this->history = new HistoryController('App\Models\Project');
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response|ResponseFactory
     */
    public function index()
    {
        $projects = Project::query()
            ->with([
                'checklists.tasks.checklist.project',
                'access_budget',
                'categories',
                'checklists.departments',
                'comments.user',
                'departments.users.departments',
                'genres',
                'managerUsers',
                'project_files',
                'project_histories.user',
                'sectors',
                'users.departments',
                'writeUsers'
            ])
            ->get();

        return inertia('Projects/ProjectManagement', [
            'projects' => ProjectShowResource::collection($projects)->resolve(),

            'projectGroups' => Project::where('is_group', 1)->get(),

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

    public function search_departments_and_users(SearchRequest $request): array
    {
        // TODO: Hier bitte gucken wie man die Policy wieder zum laufen bekommt
        /*$this->authorize('viewAny', Department::class);
        $this->authorize('viewAny', User::class);*/

        return [
            'departments' => Department::search($request->input('query'))->get(),
            'users' => UserIndexResource::collection(User::search($request->input('query'))->get())
        ];
    }

    public function search(SearchRequest $request)
    {
        $this->authorize('viewAny', Project::class);
        $projects = Project::search($request->input('query'))->get();

        return ProjectIndexResource::collection($projects)->resolve();
    }

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
     * Show the form for creating a new resource.
     *
     * @return Response|ResponseFactory
     */
    public function create()
    {
        return inertia('Projects/Create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     */
    public function store(StoreProjectRequest $request)
    {

        if (!Auth::user()->canAny([PermissionNameEnum::ADD_EDIT_OWN_PROJECT->value, PermissionNameEnum::WRITE_PROJECTS->value, PermissionNameEnum::PROJECT_MANAGEMENT->value])) {
            return response()->json(['error' => 'Not authorized to assign users to a project.'], 403);
        }

        $departments = collect($request->assigned_departments)
            ->map(fn($department) => Department::query()->findOrFail($department['id']))
            ->map(fn(Department $department) => $this->authorize('update', $department));

        $project = Project::create([
            'name' => $request->name,
            'description' => $request->description,
            'number_of_participants' => $request->number_of_participants,
            'cost_center' => $request->cost_center,
        ]);

        $project->users()->save(Auth::user(), ['access_budget' => true, 'is_manager' => false, 'can_write' => true]);

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

        $this->generateBasicBudgetValues($project);

        return Redirect::route('projects', $project)->with('success', 'Project created.');
    }

    public function generateBasicBudgetValues(Project $project)
    {
        $table = $project->table()->create([
            'name' => $project->name . ' Budgettabelle'
        ]);

        $columns = $table->columns()->createMany([
            ['name' => 'KTO', 'subName' => '', 'type' => 'empty', 'linked_first_column' => null, 'linked_second_column' => null,],
            ['name' => 'A', 'subName' => '', 'type' => 'empty', 'linked_first_column' => null, 'linked_second_column' => null,],
            ['name' => 'Position', 'subName' => '', 'type' => 'empty', 'linked_first_column' => null, 'linked_second_column' => null,],
            ['name' => date('Y') . ' €', 'subName' => 'A', 'type' => 'empty', 'linked_first_column' => null, 'linked_second_column' => null,],
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

        $earningSubPositionRow->columns()->attach($columns->pluck('id'), [
            'value' => 0,
            'verified_value' => null,
            'linked_money_source_id' => null,
        ]);

    }

    public function verifiedRequestMainPosition(Request $request): RedirectResponse
    {
        $mainPosition = MainPosition::find($request->id);
        $mainPosition->update(['is_verified' => BudgetTypesEnum::BUDGET_VERIFIED_TYPE_REQUESTED]);
        $project = $mainPosition->table()->first()->project()->first();
        $this->notificationData->title = 'Neue Verifizierungsanfrage';
        $this->notificationData->requested_position = $request->position;
        $this->notificationData->project = $project;
        $this->notificationData->type = NotificationConstEnum::NOTIFICATION_BUDGET_STATE_CHANGED;
        $this->notificationData->position = $mainPosition->id;
        $this->notificationData->created_by = Auth::user();
        $this->notificationData->requested_id = $request->user;
        $this->notificationData->changeType = BudgetTypesEnum::BUDGET_VERIFICATION_REQUEST;
        $broadcastMessage = [
            'id' => rand(1, 1000000),
            'type' => 'success',
            'message' => $this->notificationData->title
        ];
        $this->notificationController->create(User::find($request->user), $this->notificationData, $broadcastMessage);

        $mainPosition->verified()->create([
            'requested_by' => Auth::id(),
            'requested' => $request->user
        ]);
        $this->history->createHistory($project->id, 'Hauptposition „' . $mainPosition->name . '“ zur Verifizierung angefragt', 'budget');
        return back()->with('success');
    }

    public function takeBackVerification(Request $request): RedirectResponse
    {
        // create Notification Basic data
        $this->createVerificationNotificationHeader('Verifizierungsanfrage gelöscht', $request->position, BudgetTypesEnum::BUDGET_VERIFICATION_TAKE_BACK);
        if ($request->type === 'main') {
            $mainPosition = MainPosition::find($request->position['id']);
            $verifiedRequest = $mainPosition->verified()->first();

            $this->deleteOldNotification($mainPosition->id, $verifiedRequest->requested);
            // create Notification
            $this->createNotificationBody($mainPosition->table()->first()->project()->first(), $mainPosition->id, $verifiedRequest->requested);
            $broadcastMessage = [
                'id' => rand(1, 1000000),
                'type' => 'success',
                'message' => $this->notificationData->title
            ];
            $this->notificationController->create(User::find($verifiedRequest->requested), $this->notificationData, $broadcastMessage);
            $verifiedRequest->delete();
            $mainPosition->update(['is_verified' => BudgetTypesEnum::BUDGET_VERIFIED_TYPE_NOT_VERIFIED]);
            $this->history->createHistory($mainPosition->project_id, 'Hauptposition „' . $mainPosition->name . '“ Verifizierungsanfrage zurückgenommen', 'budget');
        }

        if ($request->type === 'sub') {
            $subPosition = SubPosition::find($request->position['id']);
            $mainPosition = $subPosition->mainPosition()->first();
            $verifiedRequest = $subPosition->verified()->first();

            $this->deleteOldNotification($subPosition->id, $verifiedRequest->requested);

            // create Notification
            $this->createNotificationBody($mainPosition->table()->first()->project()->first(), $subPosition->id, $verifiedRequest->requested);
            $broadcastMessage = [
                'id' => rand(1, 1000000),
                'type' => 'success',
                'message' => $this->notificationData->title
            ];
            $this->notificationController->create(User::find($verifiedRequest->requested), $this->notificationData, $broadcastMessage);
            $subPosition->update(['is_verified' => BudgetTypesEnum::BUDGET_VERIFIED_TYPE_NOT_VERIFIED]);
            $verifiedRequest->delete();
            $this->history->createHistory($mainPosition->project_id, 'Unterposition „' . $subPosition->name . '“ Verifizierungsanfrage zurückgenommen', 'budget');
        }
        return back()->with(['success']);
    }

    private function createVerificationNotificationHeader($title, $position, $type)
    {
        $this->notificationData->title = $title;
        $this->notificationData->requested_position = $position;
        $this->notificationData->created_by = Auth::user();
        $this->notificationData->type = NotificationConstEnum::NOTIFICATION_BUDGET_STATE_CHANGED;
        $this->notificationData->changeType = $type;
    }

    private function createNotificationBody($project, $positionId, $requestedId)
    {
        $this->notificationData->project = $project;
        $this->notificationData->position = $positionId;
        $this->notificationData->requested_id = $requestedId;
    }

    private function deleteOldNotification($positionId, $requestedId)
    {
        DatabaseNotification::query()
            ->whereJsonContains("data->type", "NOTIFICATION_BUDGET_STATE_CHANGED")
            ->whereJsonContains("data->position", $positionId)
            ->whereJsonContains("data->requested_id", $requestedId)
            ->whereJsonContains("data->changeType", BudgetTypesEnum::BUDGET_VERIFICATION_REQUEST)
            ->delete();
    }

    public function removeVerification(Request $request): RedirectResponse
    {
        $this->createVerificationNotificationHeader('Verifizierung in Budget aufgehoben', $request->position, BudgetTypesEnum::BUDGET_VERIFICATION_DELETED);
        if ($request->type === 'main') {
            $mainPosition = MainPosition::find($request->position['id']);
            $verifiedRequest = $mainPosition->verified()->first();
            $this->removeMainPositionCellVerifiedValue($mainPosition);

            $project = $mainPosition->table()->first()->project()->first();
            // Notification
            $this->createNotificationBody($project, $mainPosition->id, $verifiedRequest->requested);
            $broadcastMessage = [
                'id' => rand(1, 1000000),
                'type' => 'success',
                'message' => $this->notificationData->title
            ];
            $this->notificationController->create(User::find($verifiedRequest->requested), $this->notificationData, $broadcastMessage);
            $mainPosition->update(['is_verified' => BudgetTypesEnum::BUDGET_VERIFIED_TYPE_NOT_VERIFIED]);
            $verifiedRequest->delete();
            $this->history->createHistory($project->id, 'Hauptposition „' . $mainPosition->name . '“ Verifizierung aufgehoben', 'budget');
        }

        if ($request->type === 'sub') {
            $subPosition = SubPosition::find($request->position['id']);
            $mainPosition = $subPosition->mainPosition()->first();
            $verifiedRequest = $subPosition->verified()->first();
            $this->removeSubPositionCellVerifiedValue($subPosition);
            $project = $mainPosition->table()->first()->project()->first();
            // Notification
            $this->createNotificationBody($project, $subPosition->id, $verifiedRequest->requested);
            $broadcastMessage = [
                'id' => rand(1, 1000000),
                'type' => 'success',
                'message' => $this->notificationData->title
            ];
            $this->notificationController->create(User::find($verifiedRequest->requested), $this->notificationData, $broadcastMessage);
            $subPosition->update(['is_verified' => BudgetTypesEnum::BUDGET_VERIFIED_TYPE_NOT_VERIFIED]);
            $mainPosition = $subPosition->mainPosition()->first();
            $verifiedRequest->delete();
            $this->history->createHistory($project->id, 'Unterposition „' . $subPosition->name . '“ Verifizierung aufgehoben', 'budget');
        }

        return back()->with(['success']);
    }


    public function verifiedRequestSubPosition(Request $request): RedirectResponse
    {
        $subPosition = SubPosition::find($request->id);
        $subPosition->update(['is_verified' => BudgetTypesEnum::BUDGET_VERIFIED_TYPE_REQUESTED]);
        $mainPosition = $subPosition->mainPosition()->first();
        $project = $mainPosition->table()->first()->project()->first();
        $this->notificationData->title = 'Neue Verifizierungsanfrage';
        $this->notificationData->requested_position = $request->position;
        $this->notificationData->project = $project;
        $this->notificationData->type = NotificationConstEnum::NOTIFICATION_BUDGET_STATE_CHANGED;
        $this->notificationData->position = $subPosition->id;
        $this->notificationData->created_by = Auth::user();
        $this->notificationData->requested_id = $request->user;
        $this->notificationData->changeType = BudgetTypesEnum::BUDGET_VERIFICATION_REQUEST;
        $broadcastMessage = [
            'id' => rand(1, 1000000),
            'type' => 'success',
            'message' => $this->notificationData->title
        ];
        $this->notificationController->create(User::find($request->user), $this->notificationData, $broadcastMessage);

        $subPosition->verified()->create([
            'requested_by' => Auth::id(),
            'requested' => $request->user
        ]);

        $this->history->createHistory($project->id, 'Unterposition „' . $subPosition->name . '“ zur Verifizierung angefragt', 'budget');
        return back()->with('success');
    }

    public function verifiedSubPosition(Request $request): RedirectResponse
    {
        $subPosition = SubPosition::find($request->subPositionId);
        $verifiedRequest = $subPosition->verified()->first();
        $this->setSubPositionCellVerifiedValue($subPosition);
        $subPosition->update(['is_verified' => 'BUDGET_VERIFIED_TYPE_CLOSED']);

        DatabaseNotification::query()
            ->whereJsonContains("data->type", "NOTIFICATION_BUDGET_STATE_CHANGED")
            ->whereJsonContains("data->position", $subPosition->id)
            ->whereJsonContains("data->requested_id", $verifiedRequest->requested)
            ->whereJsonContains("data->changeType", BudgetTypesEnum::BUDGET_VERIFICATION_REQUEST)
            ->delete();
        $this->history->createHistory($request->project_id, 'Unterposition „' . $subPosition->name . '“ verifiziert', 'budget');
        return back()->with('success');
    }

    public function fixSubPosition(Request $request): RedirectResponse
    {
        $subPosition = SubPosition::find($request->subPositionId);
        $this->setSubPositionCellVerifiedValue($subPosition);
        $subPosition->update(['is_fixed' => true]);
        return back()->with('success');
    }

    public function unfixSubPosition(Request $request): RedirectResponse
    {
        $subPosition = SubPosition::find($request->subPositionId);
        $this->removeSubPositionCellVerifiedValue($subPosition);
        $subPosition->update(['is_fixed' => false]);
        return back()->with('success');
    }

    public function fixMainPosition(Request $request): RedirectResponse
    {
        $mainPosition = MainPosition::find($request->mainPositionId);
        $this->setMainPositionCellVerifiedValue($mainPosition);
        $mainPosition->update(['is_fixed' => true]);
        return back()->with('success');
    }

    public function unfixMainPosition(Request $request): RedirectResponse
    {
        $mainPosition = MainPosition::find($request->mainPositionId);
        $this->removeMainPositionCellVerifiedValue($mainPosition);
        $mainPosition->update(['is_fixed' => false]);
        return back()->with('success');
    }

    public function resetTable(Project $project){
        $budgetTemplateController = new BudgetTemplateController();
        $budgetTemplateController->deleteOldTable($project);
        $this->generateBasicBudgetValues($project);

        return back()->with('success');
    }

    public function verifiedMainPosition(Request $request): RedirectResponse
    {
        $mainPosition = MainPosition::find($request->mainPositionId);
        $this->setMainPositionCellVerifiedValue($mainPosition);
        $mainPosition->update(['is_verified' => 'BUDGET_VERIFIED_TYPE_CLOSED']);
        $verifiedRequest = $mainPosition->verified()->first();


        DatabaseNotification::query()
            ->whereJsonContains("data->type", "NOTIFICATION_BUDGET_STATE_CHANGED")
            ->whereJsonContains("data->position", $mainPosition->id)
            ->whereJsonContains("data->requested_id", $verifiedRequest->requested)
            ->whereJsonContains("data->changeType", BudgetTypesEnum::BUDGET_VERIFICATION_REQUEST)
            ->delete();
        $this->history->createHistory($request->project_id, 'Hauptposition „' . $mainPosition->name . '“ verifiziert', 'budget');
        return back()->with('success');
    }

    private function setSubPositionCellVerifiedValue(SubPosition $subPosition)
    {
        $subPositionRows = $subPosition->subPositionRows()->get();
        foreach ($subPositionRows as $subPositionRow) {
            $cells = $subPositionRow->cells()->get();
            foreach ($cells as $cell) {
                $cell->update(['verified_value' => @$cell->value]);
            }
        }
    }

    private function removeSubPositionCellVerifiedValue(SubPosition $subPosition)
    {
        $subPositionRows = $subPosition->subPositionRows()->get();
        foreach ($subPositionRows as $subPositionRow) {
            $cells = $subPositionRow->cells()->get();
            foreach ($cells as $cell) {
                $cell->update(['verified_value' => null]);
            }
        }
    }

    private function setMainPositionCellVerifiedValue(MainPosition $mainPosition)
    {
        $subPositions = $mainPosition->subPositions()->get();
        foreach ($subPositions as $subPosition) {
            $subPositionRows = $subPosition->subPositionRows()->get();
            foreach ($subPositionRows as $subPositionRow) {
                $cells = $subPositionRow->cells()->get();
                foreach ($cells as $cell) {
                    $cell->update(['verified_value' => @$cell->value]);
                }
            }
        }
    }

    private function removeMainPositionCellVerifiedValue(MainPosition $mainPosition)
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

    public function updateCellSource(Request $request): void
    {
        $column = ColumnCell::find($request->cell_id);
        $column->update([
            'linked_type' => $request->linked_type,
            'linked_money_source_id' => $request->money_source_id
        ]);
    }

    public function updateColumnName(Request $request): void
    {
        $column = Column::find($request->column_id);
        $column->update([
            'name' => $request->columnName
        ]);
    }

    public function columnDelete(Column $column)
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

    /**
     * @param $project_id
     * @return void
     */
    private function setColumnSubName($table_id): void
    {
        $table = Table::find($table_id);
        $columns = $table->columns()->get();

        $letters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
        $count = 0;

        foreach ($columns as $column) {
            // GUARD: When the alphabet ends. Set the count to 0 again and start from the beginning.
            if ($count > 25) {
                $count = 0;
            }
            // Skip columns without subname
            if ($column->subName === null || empty($column->subName)) {
                continue;
            }
            $column->update([
                'subName' => $letters[$count]
            ]);
            $count++;
        }
    }

    /**
     * @param Request $request
     * @return void
     */
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

            $subPositionRows = SubPositionRow::whereHas('subPosition.mainPosition', function (Builder $query) use ($request) {
                $query->where('table_id', $request->table_id);
            })->pluck('id');

            $column->subPositionRows()->attach($subPositionRows, [
                'value' => 0,
                'verified_value' => null,
                'linked_money_source_id' => null
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
                $secondColumn = ColumnCell::where('column_id', $request->second_column_id)->where('sub_position_row_id', $firstColumn->sub_position_row_id)->first();
                $sum = (int)$firstColumn->value + (int)$secondColumn->value;
                ColumnCell::create([
                    'column_id' => $column->id,
                    'sub_position_row_id' => $firstColumn->sub_position_row_id,
                    'value' => $sum,
                    'linked_money_source_id' => null
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
                $secondColumn = ColumnCell::where('column_id', $request->second_column_id)->where('sub_position_row_id', $firstColumn->sub_position_row_id)->first();
                $sum = (int)$firstColumn->value - (int)$secondColumn->value;
                ColumnCell::create([
                    'column_id' => $column->id,
                    'sub_position_row_id' => $firstColumn->sub_position_row_id,
                    'value' => $sum,
                    'linked_money_source_id' => null,
                ]);
            }
        }
    }

    /**
     * @param Request $request
     * @return void
     */
    public function updateCellValue(Request $request): void
    {
        $column = Column::find($request->column_id);
        $project = $column->table()->first()->project()->first();
        $cell = ColumnCell::where('column_id', $request->column_id)->where('sub_position_row_id', $request->sub_position_row_id)->first();

        if ($request->is_verified) {
            $this->history->createHistory($project->id, '„' . $cell->value . '“ in „' . $request->value . '“ geändert', 'budget');
        }

        $cell->update(['value' => $request->value]);
        $this->updateAutomaticCellValues($request->sub_position_row_id);
    }


    public function changeColumnColor(Request $request): RedirectResponse
    {
        $column = Column::find($request->columnId);
        $column->update(['color' => $request->color]);
        return back()->with('success', 'color changed');
    }

    public function addSubPositionRow(Request $request)
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

    /**
     * @param Request $request
     * @return void
     */
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

        $subPositionRow->columns()->attach($columns->pluck('id'), [
            'value' => 0,
            'verified_value' => null,
            'linked_money_source_id' => null,
        ]);
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

        $subPositionRow->columns()->attach($columns->pluck('id'), [
            'value' => 0,
            'verified_value' => null,
            'linked_money_source_id' => null,
        ]);
    }

    public function updateCellCalculation(Request $request)
    {

        foreach ($request->calculations as $calculation) {
            $cellCalculation = CellCalculations::find($calculation['id']);
            $cellCalculation->update([
                'name' => @$calculation['name'],
                'value' => @$calculation['value'],
                'description' => @$calculation['description']
            ]);
        }

        $cell = ColumnCell::find($request->calculations[0]['cell_id']);
        $cell->update(['value' => $cell->calculations()->sum('value')]);

        return back()->with('success');
    }

    public function addCalculation(ColumnCell $cell)
    {
        $cell->calculations()->create([
            'name' => '',
            'value' => 0,
            'description' => ''
        ]);
    }

    /**
     * This function automatically recalculates the linked columns when changes are made.
     * @param $subPositionRowId
     * @return void
     */
    private function updateAutomaticCellValues($subPositionRowId)
    {

        $rows = ColumnCell::where('sub_position_row_id', $subPositionRowId)->get();

        foreach ($rows as $row) {
            $column = Column::find($row->column_id);

            if ($column->type === 'empty') {
                continue;
            }
            $firstRowValue = ColumnCell::where('column_id', $column->linked_first_column)->where('sub_position_row_id', $subPositionRowId)->first()->value;
            $secondRowValue = ColumnCell::where('column_id', $column->linked_second_column)->where('sub_position_row_id', $subPositionRowId)->first()->value;

            $updateColumn = ColumnCell::where('sub_position_row_id', $subPositionRowId)->where('column_id', $column->id)->first();

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

    /**
     * Function to lock a column
     * @param Request $request
     * @return RedirectResponse
     */
    public function lockColumn(Request $request)
    {
        $column = Column::find($request->columnId);
        $column->update(['is_locked' => true]);
        return back()->with('success');
    }

    /**
     * Function to unlock a column
     * @param Request $request
     * @return RedirectResponse
     */
    public function unlockColumn(Request $request)
    {
        $column = Column::find($request->columnId);
        $column->update(['is_locked' => false]);
        return back()->with('success');
    }

    /**
     * Display the specified resource.
     *
     * @param Project $project
     * @return Response|ResponseFactory
     */
    public function show(Project $project, Request $request)
    {
        $project->load([
            'access_budget',
            'categories',
            'checklists.departments',
            'checklists.tasks.checklist.project',
            'checklists.tasks.user_who_done',
            'comments.user',
            'departments.users.departments',
            'genres',
            'managerUsers',
            'writeUsers',
            'project_files',
            'contracts',
            'copyright',
            'cost_center',
            'project_histories.user',
            'sectors',
            'users.departments',
        ]);

        /*dd($project->table()
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
                }, 'mainPositions.subPositions.subPositionRows.cells.column'
            ])
            ->first());*/

        $columns = $project->table()->first()->columns()->get();

        $outputColumns = [];
        foreach ($columns as $column) {
            $columnOutput = new stdClass();
            $columnOutput->id = $column->id;
            $columnOutput->name = $column->name;
            $columnOutput->subName = $column->subName;
            $columnOutput->color = $column->color;
            $columnOutput->is_locked = $column->is_locked;
            if ($column->type === 'sum') {
                $firstName = Column::where('id', $column->linked_first_column)->first()?->subName;
                $secondName = Column::where('id', $column->linked_second_column)->first()?->subName;
                $columnOutput->calculateName = $firstName . ' + ' . $secondName;
            }
            if ($column->type === 'difference') {
                $firstName = Column::where('id', $column->linked_first_column)->first()?->subName;
                $secondName = Column::where('id', $column->linked_second_column)->first()?->subName;
                $columnOutput->calculateName = $firstName . ' - ' . $secondName;
            }
            $outputColumns[] = $columnOutput;
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

        $templates = null;

        if(request('useTemplates')){
            $templates = Table::where('is_template', true)->get();
        }



        return inertia('Projects/Show', [
            'project' => new ProjectShowResource($project),

            'moneySources' => MoneySource::all(),

            // Needs to be adjusted, not correct yet.
            'projectMoneySources' => MoneySource::where('projects', 'like', "%\"{$project->id}\"%"),

            'budget' => [
                'columns' => $outputColumns,
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
                        }, 'mainPositions.subPositions.subPositionRows.cells' => function($query){
                            $query->withCount('comments')
                            ->withCount('calculations');
                        }, 'mainPositions.subPositions.subPositionRows.cells.column'
                    ])
                    ->first(),
                'selectedCell' => $selectedCell?->load(['calculations', 'comments.user', 'comments', 'column' => function ($query) {
                    $query->orderBy('created_at', 'desc');
                }]),
                'selectedRow' => $selectedRow?->load(['comments.user', 'comments' => function ($query) {
                    $query->orderBy('created_at', 'desc');
                }]),
                'templates' => $templates
            ],

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

            'checklist_templates' => ChecklistTemplate::all()->map(fn($checklist_template) => [
                'id' => $checklist_template->id,
                'name' => $checklist_template->name,
                'task_templates' => $checklist_template->task_templates->map(fn($task_template) => [
                    'id' => $task_template->id,
                    'name' => $task_template->name,
                    'description' => $task_template->description
                ]),
            ]),
            'eventTypes' => EventTypeResource::collection(EventType::all())->resolve(),

            'openTab' => $request->openTab ?: 'checklist',
            'project_id' => $project->id,
            'opened_checklists' => User::where('id', Auth::id())->first()->opened_checklists,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Project $project
     * @return Response|ResponseFactory
     */
    public function edit(Project $project)
    {
        return inertia('Projects/Edit', [
            'project' => new ProjectEditResource($project),
            'users' => User::all(),
            'departments' => Department::all()
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateProjectRequest $request
     * @param Project $project
     * @return JsonResponse|RedirectResponse
     */
    public function update(UpdateProjectRequest $request, Project $project): JsonResponse|RedirectResponse
    {
        $update_properties = $request->only('name', 'description', 'number_of_participants');

        if(!Auth::user()->hasRole(RoleNameEnum::ARTWORK_ADMIN->value)){
            // authorization
            if ((!Auth::user()->canAny([PermissionNameEnum::PROJECT_MANAGEMENT->value, PermissionNameEnum::ADD_EDIT_OWN_PROJECT->value, PermissionNameEnum::WRITE_PROJECTS->value]))
                && $project->access_budget->pluck('id')->doesntContain(Auth::id())
                && $project->managerUsers->pluck('id')->doesntContain(Auth::id())
                && $project->writeUsers->pluck('id')->doesntContain(Auth::id())
            ) {
                return response()->json(['error' => 'Not authorized to assign users to a project.'], 403);
            }
        }

        if ($request->selectedGroup === null) {
            DB::table('project_groups')->where('project_id', '=', $project->id)->delete();
        } else {
            DB::table('project_groups')->where('project_id', '=', $project->id)->delete();
            $group = Project::find($request->selectedGroup['id']);
            $group->groups()->syncWithoutDetaching($project->id);
        }


        $projectAdminsBefore = $project->access_budget()->get();
        $projectManagerBefore = $project->managerUsers()->get();
        $projectUsers = $project->users()->get();
        $oldProjectDepartments = $project->departments()->get();

        $oldProjectDescription = $project->description;
        $oldProjectName = $project->name;
        $oldProjectCategories = $project->categories()->get();
        $oldProjectGenres = $project->genres()->get();
        $oldProjectSectors = $project->sectors()->get();
        $oldProjectCostCenter = $project->cost_center;

        $project->fill($update_properties);

        $project->save();

        $project->users()->sync(collect($request->assigned_user_ids));
        $project->departments()->sync(collect($request->assigned_departments)->pluck('id'));

        $project->categories()->sync($request->projectCategoryIds);
        $project->genres()->sync($request->projectGenreIds);
        $project->sectors()->sync($request->projectSectorIds);

        $newProjectDescription = $project->description;
        $newProjectName = $project->name;
        $newProjectCategories = $project->categories()->get();

        $newProjectDepartments = $project->departments()->get();
        $projectAdminsAfter = $project->access_budget()->get();
        $projectUsersAfter = $project->users()->get();
        $projectManagerAfter = $project->managerUsers()->get();
        $newProjectGenres = $project->genres()->get();
        $newProjectSectors = $project->sectors()->get();
        $newProjectCostCenter = $project->cost_center;

        // history functions
        $this->checkProjectDescriptionChanges($project->id, $oldProjectDescription, $newProjectDescription);
        $this->checkDepartmentChanges($project->id, $oldProjectDepartments, $newProjectDepartments);
        $this->checkProjectNameChanges($project->id, $oldProjectName, $newProjectName);
        $this->checkProjectCategoryChanges($project->id, $oldProjectCategories, $newProjectCategories);
        $this->checkProjectGenreChanges($project->id, $oldProjectGenres, $newProjectGenres);
        $this->checkProjectSectorChanges($project->id, $oldProjectSectors, $newProjectSectors);
        $this->checkProjectCostCenterChanges($project->id, $oldProjectCostCenter, $newProjectCostCenter);

        // Get and check project admins, managers and users after update
        $this->createNotificationProjectMemberChanges($project, $projectAdminsBefore, $projectManagerBefore, $projectUsers, $projectAdminsAfter, $projectUsersAfter, $projectManagerAfter);

        $scheduling = new SchedulingController();
        $projectId = $project->id;
        foreach ($project->users->all() as $user) {
            $scheduling->create($user->id, 'PROJECT', $projectId);
        }
        return Redirect::back();
    }

    private function checkProjectCostCenterChanges($projectId, $oldCostCenter, $newCostCenter)
    {
        if ($newCostCenter === null && $oldCostCenter !== null) {
            $this->history->createHistory($projectId, 'Kostenträger gelöscht');
        }
        if ($oldCostCenter === null && $newCostCenter !== null) {
            $this->history->createHistory($projectId, 'Kostenträger hinzugefügt');
        }
        if ($oldCostCenter !== $newCostCenter && $oldCostCenter !== null && $newCostCenter !== null) {
            $this->history->createHistory($projectId, 'Kostenträger geändert');
        }
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
                $this->history->createHistory($projectId, 'Bereich ' . $newSector->name . ' hinzugefügt');
            }
        }

        foreach ($oldSectorIds as $oldSectorId) {
            if (!in_array($oldSectorId, $newSectorIds)) {
                $this->history->createHistory($projectId, 'Bereich ' . $oldSectorNames[$oldSectorId] . ' gelöscht');
            }
        }
    }

    public function deleteProjectFromGroup(Request $request)
    {
        $group = Project::find($request->groupId);
        $group->groups()->detach($request->projectIdToDelete);
    }

    /**
     * @param $projectId
     * @param $oldGenres
     * @param $newGenres
     * @return void
     */
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
                $this->history->createHistory($projectId, 'Genre ' . $newGenre->name . ' hinzugefügt');
            }
        }

        foreach ($oldGenreIds as $oldGenreId) {
            if (!in_array($oldGenreId, $newGenreIds)) {
                $this->history->createHistory($projectId, 'Genre ' . $oldGenreNames[$oldGenreId] . ' gelöscht');
            }
        }
    }

    /**
     * @param $projectId
     * @param $oldCategories
     * @param $newCategories
     * @return void
     */
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
                $this->history->createHistory($projectId, 'Kategorie ' . $newCategory->name . ' hinzugefügt');
            }
        }

        foreach ($oldCategoryIds as $oldCategoryId) {
            if (!in_array($oldCategoryId, $newCategoryIds)) {
                $this->history->createHistory($projectId, 'Kategorie ' . $oldCategoryNames[$oldCategoryId] . ' gelöscht');
            }
        }
    }

    /**
     * @param $projectId
     * @param $oldName
     * @param $newName
     * @return void
     */
    private function checkProjectNameChanges($projectId, $oldName, $newName): void
    {
        if ($oldName !== $newName) {
            $this->history->createHistory($projectId, 'Projektname geändert');
        }
    }

    /**
     * @param $projectId
     * @param $oldDepartments
     * @param $newDepartments
     * @return void
     */
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
                $this->history->createHistory($projectId, 'Projektteam ' . $oldDepartmentNames[$oldDepartmentId] . ' entfernt');
            }
        }
    }

    private function checkProjectDescriptionChanges($projectId, $oldDescription, $newDescription)
    {
        if (strlen($newDescription) === null) {
            $this->history->createHistory($projectId, 'Kurzbeschreibung gelöscht');
        }
        if ($oldDescription === null && $newDescription !== null) {
            $this->history->createHistory($projectId, 'Kurzbeschreibung hinzugefügt');
        }
        if ($oldDescription !== $newDescription && $oldDescription !== null && strlen($newDescription) !== null) {
            $this->history->createHistory($projectId, 'Kurzbeschreibung geändert');
        }
    }

    /**
     * @param Project $project
     * @param $projectAdminsBefore
     * @param $projectManagerBefore
     * @param $projectUsers
     * @param $projectAdminsAfter
     * @param $projectUsersAfter
     * @param $projectManagerAfter
     * @return void
     */
    private function createNotificationProjectMemberChanges(Project $project, $projectAdminsBefore, $projectManagerBefore, $projectUsers, $projectAdminsAfter, $projectUsersAfter, $projectManagerAfter): void
    {
        $userIdsBefore = [];
        $adminIdsBefore = [];
        $managerIdsBefore = [];
        $userIdsAfter = [];
        $managerIdsAfter = [];
        $adminIdsAfter = [];
        foreach ($projectUsers as $projectUser) {
            $userIdsBefore[$projectUser->id] = $projectUser->id;
        }
        foreach ($projectAdminsBefore as $adminBefore) {
            $adminIdsBefore[$adminBefore->id] = $adminBefore->id;
            if (in_array($adminBefore->id, $userIdsBefore)) {
                unset($userIdsBefore[$adminBefore->id]);
            }
        }
        foreach ($projectManagerBefore as $managerBefore) {
            $managerIdsBefore[$managerBefore->id] = $managerBefore->id;
            if (in_array($managerBefore->id, $userIdsBefore)) {
                unset($userIdsBefore[$managerBefore->id]);
            }
        }
        foreach ($projectUsersAfter as $projectUserAfter) {
            $userIdsAfter[$projectUserAfter->id] = $projectUserAfter->id;
        }
        foreach ($projectAdminsAfter as $adminAfter) {
            $adminIdsAfter[$adminAfter->id] = $adminAfter->id;
            // if added a new project admin, send notification to this user
            if (!in_array($adminAfter->id, $adminIdsBefore)) {
                $this->notificationData->title = 'Du wurdest zum Projektadmin von ' . $project->name . ' ernannt';
                $this->notificationData->project->id = $project->id;
                $this->notificationData->project->title = $project->name;
                $this->notificationData->created_by = User::where('id', Auth::id())->first();
                $broadcastMessage = [
                    'id' => rand(1, 1000000),
                    'type' => 'success',
                    'message' => $this->notificationData->title
                ];
                $this->notificationController->create($adminAfter, $this->notificationData, $broadcastMessage);
            }
            if (in_array($adminAfter->id, $userIdsAfter)) {
                unset($userIdsAfter[$adminAfter->id]);
            }
        }
        foreach ($projectManagerAfter as $managerAfter) {
            $managerIdsAfter[$managerAfter->id] = $managerAfter->id;
            // if added a new project manager, send notification to this user
            if (!in_array($managerAfter->id, $managerIdsBefore)) {
                $this->notificationData->title = 'Du wurdest zum Projektmanager von ' . $project->name . ' ernannt';
                $this->notificationData->project->id = $project->id;
                $this->notificationData->project->title = $project->name;
                $this->notificationData->created_by = User::where('id', Auth::id())->first();
                $broadcastMessage = [
                    'id' => rand(1, 1000000),
                    'type' => 'success',
                    'message' => $this->notificationData->title
                ];
                $this->notificationController->create($managerAfter, $this->notificationData, $broadcastMessage);
            }
            if (in_array($managerAfter->id, $userIdsAfter)) {
                unset($userIdsAfter[$managerAfter->id]);
            }
        }
        // check if user remove as project admin
        foreach ($adminIdsBefore as $adminBefore) {
            if (!in_array($adminBefore, $adminIdsAfter)) {
                $user = User::find($adminBefore);
                $this->notificationData->title = 'Du wurdest als Projektadmin von ' . $project->name . ' gelöscht';
                $this->notificationData->project->id = $project->id;
                $this->notificationData->project->title = $project->name;
                $this->notificationData->created_by = User::where('id', Auth::id())->first();
                $broadcastMessage = [
                    'id' => rand(1, 1000000),
                    'type' => 'error',
                    'message' => $this->notificationData->title
                ];
                $this->notificationController->create($user, $this->notificationData, $broadcastMessage);
            }
        }
        // check if user remove as project manager
        foreach ($managerIdsBefore as $managerBefore) {
            if (!in_array($managerBefore, $managerIdsAfter)) {
                $user = User::find($managerBefore);
                $this->notificationData->title = 'Du wurdest als Projektmanager von ' . $project->name . ' gelöscht';
                $this->notificationData->project->id = $project->id;
                $this->notificationData->project->title = $project->name;
                $this->notificationData->created_by = User::where('id', Auth::id())->first();
                $broadcastMessage = [
                    'id' => rand(1, 1000000),
                    'type' => 'error',
                    'message' => $this->notificationData->title
                ];
                $this->notificationController->create($user, $this->notificationData, $broadcastMessage);
            }
        }
        foreach ($userIdsAfter as $userIdAfter) {
            if (!in_array($userIdAfter, $userIdsBefore)) {
                $user = User::find($userIdAfter);
                $this->notificationData->title = 'Du wurdest zu ' . $project->name . ' hinzugefügt';
                $this->notificationData->project->id = $project->id;
                $this->notificationData->project->title = $project->name;
                $this->notificationData->created_by = User::where('id', Auth::id())->first();
                $broadcastMessage = [
                    'id' => rand(1, 1000000),
                    'type' => 'success',
                    'message' => $this->notificationData->title
                ];
                $this->notificationController->create($user, $this->notificationData, $broadcastMessage);
            }
        }
        foreach ($userIdsBefore as $userIdBefore) {
            if (!in_array($userIdBefore, $userIdsAfter)) {
                $user = User::find($userIdBefore);
                $this->notificationData->title = 'Du wurdest aus ' . $project->name . ' gelöscht';
                $this->notificationData->project->id = $project->id;
                $this->notificationData->project->title = $project->name;
                $this->notificationData->created_by = User::where('id', Auth::id())->first();
                $broadcastMessage = [
                    'id' => rand(1, 1000000),
                    'type' => 'success',
                    'message' => $this->notificationData->title
                ];
                $this->notificationController->create($user, $this->notificationData, $broadcastMessage);
            }
        }
    }

    /**
     * Duplicates the project whose id is passed in the request
     */
    public function duplicate(Project $project, HistoryService $historyService)
    {
        // authorization
        if ($project->users->isNotEmpty() || !Auth::user()->hasRole(RoleNameEnum::ARTWORK_ADMIN->value)) {
            if ((!Auth::user()->canAny([PermissionNameEnum::PROJECT_MANAGEMENT->value, PermissionNameEnum::ADD_EDIT_OWN_PROJECT->value, PermissionNameEnum::WRITE_PROJECTS->value]))
                && $project->access_budget->pluck('id')->doesntContain(Auth::id())
                && $project->managerUsers->pluck('id')->doesntContain(Auth::id())
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

        $project->checklists->map(function (Checklist $checklist) use ($newProject) {
            /** @var Checklist $replicated_checklist */
            $replicated_checklist = $checklist->replicate()->fill(['project_id' => $newProject->id]);
            $replicated_checklist->save();
            $replicated_checklist->departments()->sync($checklist->departments->pluck('id'));

            $checklist->tasks->map(function (Task $task) use ($replicated_checklist) {
                $replicated_task = $task->replicate(['deadline', 'done', 'done_at',])
                    ->fill(['checklist_id' => $replicated_checklist->id, 'done' => false]);

                $replicated_task->save();
            });
        });


        $newProject->users()->attach([Auth::id() => ['access_budget' => true]]);
        $newProject->categories()->sync($project->categories->pluck('id'));
        $newProject->sectors()->sync($project->sectors->pluck('id'));
        $newProject->genres()->sync($project->genres->pluck('id'));
        $newProject->departments()->sync($project->departments->pluck('id'));
        $newProject->users()->sync($project->users->pluck('id'));

        $historyService->updateHistory($project, config('history.project.duplicated'));

        return Redirect::route('projects.show', $newProject->id)->with('success', 'Project created.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Project $project
     * @return RedirectResponse
     */
    public function destroy(Project $project)
    {
        $project->events()->delete();

        foreach ($project->checklists() as $checklist) {
            $checklist->tasks()->delete();
        }

        //create notification data
        $this->notificationData->title = $project->name . ' wurde gelöscht';
        $this->notificationData->project->id = $project->id;
        $this->notificationData->project->title = $project->name;
        $this->notificationData->created_by = User::where('id', Auth::id())->first();

        // send notification to all users in project
        $this->notificationController->create($project->users->all(), $this->notificationData);

        $project->checklists()->delete();

        $project->delete();

        return Redirect::route('projects')->with('success', 'Project moved to trash');
    }

    public function forceDelete(int $id)
    {
        /** @var Project $project */
        $project = Project::onlyTrashed()->findOrFail($id);

        $project->forceDelete();
        $project->events()->withTrashed()->forceDelete();
        $project->project_histories()->delete();

        return Redirect::route('projects.trashed')->with('success', 'Project deleted');
    }

    public function restore(int $id)
    {
        $project = Project::onlyTrashed()->findOrFail($id);

        $project->restore();
        $project->events()->withTrashed()->restore();

        return Redirect::route('projects.trashed')->with('success', 'Project restored');
    }

    public function getTrashed()
    {
        return inertia('Trash/Projects', [
            'trashed_projects' => ProjectIndexResource::collection(Project::onlyTrashed()->get())->resolve()
        ]);
    }

    public function deleteRow(SubPositionRow $row)
    {
        $row->forceDelete();
    }

    public function deleteMainPosition(MainPosition $mainPosition)
    {
        $subPositions = $mainPosition->subPositions()->get();
        foreach ($subPositions as $subPosition) {
            $subRows = $subPosition->subPositionRows()->get();

            foreach ($subRows as $subRow) {
                $cells = $subRow->cells()->get();
                foreach ($cells as $cell) {
                    /*$comments = $cells->comments()->get();
                    foreach ($comments as $comment){
                        $comment->delete();
                    }*/
                    $cell->delete();
                }
                $subRow->delete();
            }
            $subPosition->delete();
        }
        $mainPosition->delete();
    }

    public function deleteSubPosition(SubPosition $subPosition)
    {
        $subRows = $subPosition->subPositionRows()->get();

        foreach ($subRows as $subRow) {
            $cells = $subRow->cells()->get();
            foreach ($cells as $cell) {
                /*$comments = $cells->comments()->get();
                foreach ($comments as $comment){
                    $comment->delete();
                }*/
                $cell->delete();
            }
            $subRow->delete();
        }
        $subPosition->delete();
    }
    public function updateCommentedStatusOfRow(Request $request, SubPositionRow $row): RedirectResponse
    {
        $cells = $row->cells()->get();
        $row->update(['commented' => $request->commented]);
        foreach ( $cells as $cell){
            $cell->update(['commented' => $request->commented]);
        }
        return back()->with('success');
    }
    public function updateCommentedStatusOfCell(Request $request, ColumnCell $columnCell): RedirectResponse
    {
        $columnCell->update(['commented' => $request->commented]);
        return back()->with('success');
    }
}
