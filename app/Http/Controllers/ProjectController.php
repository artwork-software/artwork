<?php

namespace App\Http\Controllers;

use App\Enums\NotificationConstEnum;
use App\Http\Requests\SearchRequest;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Resources\EventTypeResource;
use App\Http\Resources\ProjectEditResource;
use App\Http\Resources\ProjectIndexResource;
use App\Http\Resources\ProjectShowResource;
use App\Models\Category;
use App\Models\Checklist;
use App\Models\ChecklistTemplate;
use App\Models\Department;
use App\Models\EventType;
use App\Models\Genre;
use App\Models\Project;
use App\Models\Sector;
use App\Models\Task;
use App\Models\User;
use App\Support\Services\HistoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Response;
use Inertia\ResponseFactory;
use stdClass;

class ProjectController extends Controller
{
    // init empty notification controller
    protected ?NotificationController $notificationController = null;
    protected ?stdClass $notificationData = null;

    public function __construct()
    {
        $this->authorizeResource(Project::class);

        // init notification controller
        $this->notificationController = new NotificationController();
        $this->notificationData = new stdClass();
        $this->notificationData->project = new stdClass();
        $this->notificationData->type = NotificationConstEnum::NOTIFICATION_PROJECT;
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
    public function store(StoreProjectRequest $request, HistoryService $historyService)
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
        $historyService->projectUpdated($project);

        $project->users()->save(Auth::user(), ['is_admin' => true, 'is_manager' => false]);

        if ($request->assigned_user_ids) {
            $project->users()->sync(collect($request->assigned_user_ids));
        }

        $project->categories()->sync($request->assignedCategoryIds);
        $project->sectors()->sync($request->assignedSectorIds);
        $project->genres()->sync($request->assignedGenreIds);
        $project->departments()->sync($departments->pluck('id'));

        return Redirect::route('projects', $project)->with('success', 'Project created.');
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

        return inertia('Projects/Show', [
            'project' => new ProjectShowResource($project),

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
    public function update(UpdateProjectRequest $request, Project $project, HistoryService $historyService)
    {
        $update_properties = $request->only('name', 'description', 'number_of_participants', 'cost_center');

        // authorization
        if ((! Auth::user()->canAny(['update users', 'create and edit projects', 'admin projects']))
            && $project->adminUsers->pluck('id')->doesntContain(Auth::id())
            && $project->managerUsers->pluck('id')->doesntContain(Auth::id())) {
            return response()->json(['error' => 'Not authorized to assign users to a project.'], 403);
        }

        // Get project admin and manager before update
        $adminIdsBefore = [];
        $managerIdsBefore = [];
        $projectAdminsBefore = $project->adminUsers()->get();
        $projectManagerBefore = $project->managerUsers()->get();
        foreach ($projectAdminsBefore as $adminBefore){
            $adminIdsBefore[] = $adminBefore->id;
        }
        foreach ($projectManagerBefore as $managerBefore){
            $managerIdsBefore[] = $managerBefore->id;
        }

        $project->fill($update_properties);

        $project->save();

        $project->users()->sync(collect($request->assigned_user_ids));
        $project->departments()->sync(collect($request->assigned_departments)->pluck('id'));

        $project->categories()->sync($request->projectCategoryIds);
        $project->genres()->sync($request->projectGenreIds);
        $project->sectors()->sync($request->projectSectorIds);
        $historyService->updateHistory($project,'Eigenschaften angepasst.');
        $historyService->projectUpdated($project);

        // Get and check project admins and managers after update
        $adminIdsAfter = [];
        $projectAdminsAfter = $project->adminUsers()->get();
        $managerIdsAfter = [];
        $projectManagerAfter = $project->managerUsers()->get();

        foreach ($projectAdminsAfter as $adminAfter){
            $adminIdsAfter[] = $adminAfter->id;
            // if added a new project admin, send notification to this user
            if(!in_array($adminAfter->id, $adminIdsBefore)){
                $this->notificationData->title = 'Du wurdest als Projektadmin von ' . $project->name . ' ernannt';
                $this->notificationData->project->id = $project->id;
                $this->notificationData->project->title = $project->name;
                $this->notificationData->created_by = Auth::id();
                $this->notificationController->create($adminAfter, $this->notificationData);
            }
        }

        foreach ($projectManagerAfter as $managerAfter){
            $managerIdsAfter[] = $managerAfter->id;
            // if added a new project manager, send notification to this user
            if(!in_array($managerAfter->id, $managerIdsBefore)){
                $this->notificationData->title = 'Du wurdest als Projektmanager von ' . $project->name . ' ernannt';
                $this->notificationData->project->id = $project->id;
                $this->notificationData->project->title = $project->name;
                $this->notificationData->created_by = Auth::id();
                $this->notificationController->create($managerAfter, $this->notificationData);
            }
        }

        // check if user remove as project admin
        foreach ($adminIdsBefore as $adminBefore){
            if(!in_array($adminBefore, $adminIdsAfter)){
                $user = User::find($adminBefore);
                $this->notificationData->title = 'Du wurdest als Projektadmin von ' . $project->name . ' entfernt';
                $this->notificationData->project->id = $project->id;
                $this->notificationData->project->title = $project->name;
                $this->notificationData->created_by = Auth::id();
                $this->notificationController->create($user, $this->notificationData);
            }
        }

        // check if user remove as project manager
        foreach ($managerIdsBefore as $managerBefore){
            if(!in_array($managerBefore, $managerIdsAfter)){
                $user = User::find($managerBefore);
                $this->notificationData->title = 'Du wurdest als Projektmanager von ' . $project->name . ' entfernt';
                $this->notificationData->project->id = $project->id;
                $this->notificationData->project->title = $project->name;
                $this->notificationData->created_by = Auth::id();
                $this->notificationController->create($user, $this->notificationData);
            }
        }

        $task = new SchedulingController();
        $projectId = $project->id;
        foreach($project->users->all() as $user ){
            $task->create($user->id, 'PROJECT', $projectId);
        }
        return Redirect::back();
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
        $this->notificationData->title = 'Projekt ' . $project->name . ' wurde gelöscht';
        $this->notificationData->project->id = $project->id;
        $this->notificationData->project->title = $project->name;
        $this->notificationData->created_by = Auth::id();

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
