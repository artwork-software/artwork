<?php

namespace App\Http\Controllers;

use Antonrom\ModelChangesHistory\Models\Change;
use App\Enums\BudgetTypesEnum;
use App\Enums\NotificationConstEnum;
use App\Http\Requests\SearchRequest;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Resources\BudgetResource;
use App\Http\Resources\EventTypeResource;
use App\Http\Resources\ProjectEditResource;
use App\Http\Resources\ProjectIndexResource;
use App\Http\Resources\ProjectShowResource;
use App\Models\Category;
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
use App\Models\Task;
use App\Models\User;
use App\Support\Services\HistoryService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Inertia\Response;
use Inertia\ResponseFactory;
use stdClass;

class ProjectController extends Controller
{
    // init empty notification controller
    protected ?NotificationController $notificationController = null;
    protected ?stdClass $notificationData = null;
    protected ?HistoryController $history = null;

    public function __construct()
    {
        $this->authorizeResource(Project::class);

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
                'adminUsers',
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
            ])
            ->get();

        return inertia('Projects/ProjectManagement', [
            'projects' => ProjectShowResource::collection($projects)->resolve(),

            'projectGroups' => Project::where('is_group', 1)->get(),

            'users' => User::all(),

            'categories' => Category::query()->with('projects')->get()->map(fn ($category) => [
                'id' => $category->id,
                'name' => $category->name,
                'projects' => $category->projects
            ]),

            'genres' => Genre::query()->with('projects')->get()->map(fn ($genre) => [
                'id' => $genre->id,
                'name' => $genre->name,
                'projects' => $genre->projects
            ]),

            'sectors' => Sector::query()->with('projects')->get()->map(fn ($sector) => [
                'id' => $sector->id,
                'name' => $sector->name,
                'projects' => $sector->projects
            ]),
        ]);
    }

    public function search_departments_and_users(SearchRequest $request): array
    {
        $this->authorize('viewAny', Department::class);
        $this->authorize('viewAny', User::class);

        return [
            'departments' => Department::search($request->input('query'))->get(),
            'users' => User::search($request->input('query'))->get()
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

        if (! Auth::user()->canAny(['update users', 'create and edit projects', 'admin projects'])) {
            return response()->json(['error' => 'Not authorized to assign users to a project.'], 403);
        }

        if (! Auth::user()->canAny(['update users', 'create and edit projects', 'admin projects'])) {
            return response()->json(['error' => 'Not authorized to assign users to a project.'], 403);
        }

        $departments = collect($request->assigned_departments)
            ->map(fn ($department) => Department::query()->findOrFail($department['id']))
            ->map(fn (Department $department) => $this->authorize('update', $department));

        $project = Project::create([
            'name' => $request->name,
            'description' => $request->description,
            'number_of_participants' => $request->number_of_participants,
            'cost_center' => $request->cost_center,
        ]);

        $project->users()->save(Auth::user(), ['is_admin' => true, 'is_manager' => false]);

        if($request->isGroup){
            $project->is_group = true;
            $project->groups()->sync(collect($request->projects));
            $project->save();
        }

        if(!$request->isGroup && !empty($request->selectedGroup)){
            $group = new ProjectGroups();
            $group->create([
                'project_groups_id' => $project->id,
                'project_id' => $request->selectedGroup['id']
            ]);
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
        $columns = $project->columns()->createMany([
            ['name' => 'KTO', 'subName' => ''],
            ['name' => 'A', 'subName' => ''],
            ['name' => 'Position', 'subName' => ''],
            ['name' => date('Y') . ' €', 'subName' => 'A'],
        ]);

        $costMainPosition = $project->mainPositions()->create([
            'type' => BudgetTypesEnum::BUDGET_TYPE_COST,
            'name' => 'Hauptpostion',
            'position' => $project->mainPositions()
                    ->where('type', BudgetTypesEnum::BUDGET_TYPE_COST)->max('position') + 1
        ]);

        $earningMainPosition = $project->mainPositions()->create([
            'type' => BudgetTypesEnum::BUDGET_TYPE_EARNING,
            'name' => 'Hauptpostion',
            'position' => $project->mainPositions()
                    ->where('type', BudgetTypesEnum::BUDGET_TYPE_EARNING)->max('position') + 1
        ]);

        $costSubPosition = $costMainPosition->subPositions()->create([
            'name' => 'Unterposition',
            'position' => $costMainPosition->subPositions()->max('position') + 1
        ]);

        $earningSubPosition = $earningMainPosition->subPositions()->create([
            'name' => 'Unterposition',
            'position' => $earningMainPosition->subPositions()->max('position') + 1
        ]);

        $costSubPositionRow = $costSubPosition->subPositionRows()->create([
            'commented' => false,
        ]);

        $earningSubPositionRow = $earningSubPosition->subPositionRows()->create([
            'commented' => false,
        ]);

        $costSubPositionRow->columns()->attach($columns->pluck('id'), [
            'value' => '',
            'linked_money_source_id' => null,
            'type' => null,
        ]);

        $earningSubPositionRow->columns()->attach($columns->pluck('id'), [
            'value' => '',
            'linked_money_source_id' => null,
            'type' => null,
        ]);

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

    /**
     * @param $project_id
     * @return void
     */
    private function setColumnSubName($project_id): void
    {
        $project = Project::find($project_id);
        $columns = $project->columns()->get();

        $letters = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'];
        $count = 0;

        foreach($columns as $column){
            // GUARD: When the alphabet ends. Set the count to 0 again and start from the beginning.
            if($count > 25){
                $count = 0;
            }
            // Skip columns without subname
            if($column->subName === null || empty($column->subName)){
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
        $project = Project::find($request->project_id);
        if($request->column_type === 'empty') {

            $column = $project->columns()->create([
                'name' => 'empty',
                'subName' => '-'
            ]);
            $this->setColumnSubName($request->project_id);

            $subPositionRows = SubPositionRow::whereHas('subPosition.mainPosition', function (Builder $query) use ($request) {
                $query->where('project_id', $request->project_id);
            })->pluck('id');

            $column->subPositionRows()->attach($subPositionRows, [
                'value' => '',
                'linked_money_source_id' => null,
                'type' => 'empty',
                'linked_first_column' => null,
                'linked_second_column' => null,
            ]);

        }

        if($request->column_type === 'sum'){
            $firstColumns = ColumnCell::where('column_id', $request->first_column_id)->get();
            $column = $project->columns()->create([
                'name' => 'sum',
                'subName' => '-'
            ]);
            $this->setColumnSubName($request->project_id);
            foreach ($firstColumns as $firstColumn){
                $secondColumn = ColumnCell::where('column_id', $request->second_column_id)->where('sub_position_row_id', $firstColumn->sub_position_row_id)->first();
                $sum = (int)$firstColumn->value + (int)$secondColumn->value;
                ColumnCell::create([
                    'column_id' => $column->id,
                    'sub_position_row_id' => $firstColumn->sub_position_row_id,
                    'value' => $sum,
                    'linked_money_source_id' => null,
                    'type' => 'sum',
                    'linked_first_column' => $firstColumn->column_id,
                    'linked_second_column' => $secondColumn->column_id,
                ]);
            }
        }

        if($request->column_type === 'difference'){
            $firstColumns = ColumnCell::where('column_id', $request->first_column_id)->get();
            $column = $project->columns()->create([
                'name' => 'difference',
                'subName' => '-'
            ]);
            $this->setColumnSubName($request->project_id);
            foreach ($firstColumns as $firstColumn){
                $secondColumn = ColumnCell::where('column_id', $request->second_column_id)->where('sub_position_row_id', $firstColumn->sub_position_row_id)->first();
                $sum = (int)$firstColumn->value - (int)$secondColumn->value;
                ColumnCell::create([
                    'column_id' => $column->id,
                    'sub_position_row_id' => $firstColumn->sub_position_row_id,
                    'value' => $sum,
                    'linked_money_source_id' => null,
                    'type' => 'difference',
                    'linked_first_column' => $firstColumn->column_id,
                    'linked_second_column' => $secondColumn->column_id,
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
        ColumnCell::where('column_id', $request->column_id)->where('sub_position_row_id', $request->sub_position_row_id)->update(['value' => $request->value]);
    }


    public function addSubPositionRow(Request $request){
        $project = Project::find($request->project_id);
        $columns = $project->columns()->get();
        $subPosition = SubPosition::find($request->sub_position_id);

        $subPositionRow = $subPosition->subPositionRows()->create([
            'commented' => false,
        ]);

        $subPositionRow->columns()->attach($columns->pluck('id'), [
            'value' => '',
            'linked_money_source_id' => null,
            'type' => null,
        ]);
    }

    /**
     * @param Request $request
     * @return void
     */
    public function addMainPosition(Request $request): void
    {
        $project = Project::find($request->project_id);
        $columns = $project->columns()->get();

        MainPosition::query()
            ->where('project_id', $request->project_id)
            ->where('position', '>', $request->positionBefore)
            ->increment('position');

        $mainPosition = $project->mainPositions()->create([
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
        ]);

        $subPositionRow->columns()->attach($columns->pluck('id'), [
            'value' => '',
            'linked_money_source_id' => null,
            'type' => null,
        ]);

    }

    public function addSubPosition(Request $request): void
    {

        $project = Project::find($request->project_id);
        $columns = $project->columns()->get();
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
        ]);

        $subPositionRow->columns()->attach($columns->pluck('id'), [
            'value' => '',
            'linked_money_source_id' => null,
            'type' => null,
        ]);
    }

    public function updateCellCalculation(Request $request){
        $cell = ColumnCell::where('column_id', $request->column_id)->where('sub_position_row_id', $request->sub_position_row_id)->first();
        $cell->update(['calculations' => json_encode($request->calculations)]);
        $cell->update(['value' => $request->calculationSum]);
        return back()->with('success');
    }

    /**
     * Display the specified resource.
     *
     * @param  Project  $project
     * @return Response|ResponseFactory
     */
    public function show(Project $project, Request $request)
    {
        $project->load([
            'adminUsers',
            'categories',
            'checklists.departments',
            'checklists.tasks.checklist.project',
            'checklists.tasks.user_who_done',
            'comments.user',
            'departments.users.departments',
            'genres',
            'managerUsers',
            'project_files',
            'project_histories.user',
            'sectors',
            'users.departments',
        ]);

        $columns = $project->columns()->get();


        return inertia('Projects/Show', [
            'project' => new ProjectShowResource($project),

            'moneySources' => MoneySource::all(),

            'budget' => [
                'columns' => $columns,
                'table' => $project->mainPositions()
                    ->with(['subPositions' => function($query) {
                        return $query->orderBy('position');
                    }, 'subPositions.subPositionRows.columns'])
                    ->orderBy('position')
                    ->get()
            ],

            'categories' => Category::all(),
            'projectCategoryIds' => $project->categories()->pluck('category_id'),
            'projectCategories' => $project->categories,

            'genres' => Genre::all(),
            'projectGenreIds' => $project->genres()->pluck('genre_id'),
            'projectGenres' => $project->genres,

            'sectors' => Sector::all(),
            'projectSectorIds' => $project->sectors()->pluck('sector_id'),
            'projectSectors' => $project->sectors,

            'checklist_templates' => ChecklistTemplate::all()->map(fn ($checklist_template) => [
                'id' => $checklist_template->id,
                'name' => $checklist_template->name,
                'task_templates' => $checklist_template->task_templates->map(fn ($task_template) => [
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
     * @param  Project  $project
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
     * @param  UpdateProjectRequest  $request
     * @param  Project  $project
     * @return JsonResponse|RedirectResponse
     */
    public function update(UpdateProjectRequest $request, Project $project): JsonResponse|RedirectResponse
    {
        $update_properties = $request->only('name', 'description', 'number_of_participants', 'cost_center');

        // authorization
        if ((! Auth::user()->canAny(['update users', 'create and edit projects', 'admin projects']))
            && $project->adminUsers->pluck('id')->doesntContain(Auth::id())
            && $project->managerUsers->pluck('id')->doesntContain(Auth::id())) {
            return response()->json(['error' => 'Not authorized to assign users to a project.'], 403);
        }

        $projectAdminsBefore = $project->adminUsers()->get();
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
        $projectAdminsAfter = $project->adminUsers()->get();
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
        foreach($project->users->all() as $user ){
            $scheduling->create($user->id, 'PROJECT', $projectId);
        }
        return Redirect::back();
    }

    private function checkProjectCostCenterChanges($projectId, $oldCostCenter, $newCostCenter)
    {
        if($newCostCenter === null && $oldCostCenter !== null){
            $this->history->createHistory($projectId, 'Kostenträger gelöscht');
        }
        if($oldCostCenter === null && $newCostCenter !== null){
            $this->history->createHistory($projectId, 'Kostenträger hinzugefügt');
        }
        if($oldCostCenter !== $newCostCenter && $oldCostCenter !== null && $newCostCenter !== null){
            $this->history->createHistory($projectId, 'Kostenträger geändert');
        }
    }

    private function checkProjectSectorChanges($projectId, $oldSectors, $newSectors): void
    {
        $oldSectorIds = [];
        $oldSectorNames = [];
        $newSectorIds = [];

        foreach ($oldSectors as $oldSector){
            $oldSectorIds[] = $oldSector->id;
            $oldSectorNames[$oldSector->id] = $oldSector->name;
        }

        foreach ($newSectors as $newSector){
            $newSectorIds[] = $newSector->id;
            if(!in_array($newSector->id, $oldSectorIds)){
                $this->history->createHistory($projectId, 'Bereich ' . $newSector->name . ' hinzugefügt');
            }
        }

        foreach ($oldSectorIds as $oldSectorId){
            if(!in_array($oldSectorId, $newSectorIds)){
                $this->history->createHistory($projectId, 'Bereich ' . $oldSectorNames[$oldSectorId] . ' gelöscht');
            }
        }
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

        foreach ($oldGenres as $oldGenre){
            $oldGenreIds[] = $oldGenre->id;
            $oldGenreNames[$oldGenre->id] = $oldGenre->name;
        }

        foreach ($newGenres as $newGenre){
            $newGenreIds[] = $newGenre->id;
            if(!in_array($newGenre->id, $oldGenreIds)){
                $this->history->createHistory($projectId, 'Genre ' . $newGenre->name . ' hinzugefügt');
            }
        }

        foreach ($oldGenreIds as $oldGenreId){
            if(!in_array($oldGenreId, $newGenreIds)){
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

        foreach ($oldCategories as $oldCategory){
            $oldCategoryIds[] = $oldCategory->id;
            $oldCategoryNames[$oldCategory->id] = $oldCategory->name;
        }

        foreach ($newCategories as $newCategory){
            $newCategoryIds[] = $newCategory->id;
            if(!in_array($newCategory->id, $oldCategoryIds)){
                $this->history->createHistory($projectId, 'Kategorie ' . $newCategory->name . ' hinzugefügt');
            }
        }

        foreach ($oldCategoryIds as $oldCategoryId){
            if(!in_array($oldCategoryId, $newCategoryIds)){
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
        if($oldName !== $newName){
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
        foreach ($oldDepartments as $oldDepartment){
            $oldDepartmentIds[] = $oldDepartment->id;
            $oldDepartmentNames[$oldDepartment->id] = $oldDepartment->name;
        }

        foreach ($newDepartments as $newDepartment) {
            $newDepartmentIds[] = $newDepartment->id;
            if(!in_array($newDepartment->id, $oldDepartmentIds)){
                $this->history->createHistory($projectId, 'Projektteam ' . $newDepartment->name . ' hinzugefügt');
            }
        }

        foreach ($oldDepartmentIds as $oldDepartmentId){
            if(!in_array($oldDepartmentId, $newDepartmentIds)){
                $this->history->createHistory($projectId, 'Projektteam ' . $oldDepartmentNames[$oldDepartmentId] . ' entfernt');
            }
        }
    }

    private function checkProjectDescriptionChanges($projectId, $oldDescription, $newDescription)
    {
        if(strlen($newDescription) === null){
            $this->history->createHistory($projectId, 'Kurzbeschreibung gelöscht');
        }
        if($oldDescription === null && $newDescription !== null){
            $this->history->createHistory($projectId, 'Kurzbeschreibung hinzugefügt');
        }
        if($oldDescription !== $newDescription && $oldDescription !== null && strlen($newDescription) !== null){
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
        foreach ($projectUsers as $projectUser){
            $userIdsBefore[$projectUser->id] = $projectUser->id;
        }
        foreach ($projectAdminsBefore as $adminBefore){
            $adminIdsBefore[$adminBefore->id] = $adminBefore->id;
            if(in_array($adminBefore->id, $userIdsBefore)){
                unset($userIdsBefore[$adminBefore->id]);
            }
        }
        foreach ($projectManagerBefore as $managerBefore){
            $managerIdsBefore[$managerBefore->id] = $managerBefore->id;
            if(in_array($managerBefore->id, $userIdsBefore)){
                unset($userIdsBefore[$managerBefore->id]);
            }
        }
        foreach ($projectUsersAfter as $projectUserAfter){
            $userIdsAfter[$projectUserAfter->id] = $projectUserAfter->id;
        }
        foreach ($projectAdminsAfter as $adminAfter){
            $adminIdsAfter[$adminAfter->id] = $adminAfter->id;
            // if added a new project admin, send notification to this user
            if(!in_array($adminAfter->id, $adminIdsBefore)){
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
            if(in_array($adminAfter->id, $userIdsAfter)){
                unset($userIdsAfter[$adminAfter->id]);
            }
        }
        foreach ($projectManagerAfter as $managerAfter){
            $managerIdsAfter[$managerAfter->id] = $managerAfter->id;
            // if added a new project manager, send notification to this user
            if(!in_array($managerAfter->id, $managerIdsBefore)){
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
            if(in_array($managerAfter->id, $userIdsAfter)){
                unset($userIdsAfter[$managerAfter->id]);
            }
        }
        // check if user remove as project admin
        foreach ($adminIdsBefore as $adminBefore){
            if(!in_array($adminBefore, $adminIdsAfter)){
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
        foreach ($managerIdsBefore as $managerBefore){
            if(!in_array($managerBefore, $managerIdsAfter)){
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
        foreach ($userIdsAfter as $userIdAfter){
            if(!in_array($userIdAfter, $userIdsBefore)){
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
        foreach ($userIdsBefore as $userIdBefore){
            if(!in_array($userIdBefore, $userIdsAfter)){
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
        if ($project->users->isNotEmpty()) {
            if ((! Auth::user()->canAny(['update users', 'create and edit projects', 'admin projects']))
                && $project->adminUsers->pluck('id')->doesntContain(Auth::id())
                && $project->managerUsers->pluck('id')->doesntContain(Auth::id())) {
                return response()->json(['error' => 'Not authorized to assign users to a project.'], 403);
            }
        }

        if ($project->departments->isNotEmpty()) {
            $project->departments->map(fn ($department) => $this->authorize('update', $department));
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

        $newProject->users()->attach([Auth::id() => ['is_admin' => true]]);
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
     * @param  Project  $project
     * @return RedirectResponse
     */
    public function destroy(Project $project)
    {
        $project->events()->delete();

        foreach ($project->checklists() as $checklist) {
            $checklist->tasks()->delete();
        }

        //create notification data
        $this->notificationData->title =  $project->name . ' wurde gelöscht';
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




}
