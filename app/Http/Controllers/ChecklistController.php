<?php

namespace App\Http\Controllers;

use App\Enums\PermissionNameEnum;
use App\Enums\RoleNameEnum;
use App\Http\Requests\ChecklistUpdateRequest;
use App\Http\Resources\ChecklistShowResource;
use App\Models\Checklist;
use App\Models\ChecklistTemplate;
use App\Models\Project;
use App\Models\ProjectHistory;
use App\Models\Task;
use App\Support\Services\HistoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

class ChecklistController extends Controller
{
    protected ?HistoryController $history = null;

    public function __construct()
    {
        $this->authorizeResource(Checklist::class);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function create()
    {
        return inertia('Checklists/Create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function store(Request $request)
    {
        $this->authorize('createProperties', Project::find($request->project_id));
        //Check whether checklist should be created on basis of a template
        if ($request->template_id) {
            $this->createFromTemplate($request);
        } else {
            $this->createWithoutTemplate($request);
        }

        $this->history = new HistoryController('App\Models\Project');
        $this->history->createHistory($request->project_id, 'Checkliste ' . $request->name. ' hinzugefügt');


        ProjectHistory::create([
            "user_id" => Auth::id(),
            "project_id" => $request->project_id,
            "description" => "Checkliste $request->name angelegt"
        ]);

        return Redirect::back()->with('success', 'Checklist created.');
    }

    /**
     * Creates a checklist on basis of a ChecklistTemplate
     * @param  Request  $request
     */
    protected function createFromTemplate(Request $request)
    {
        $template = ChecklistTemplate::where('id', $request->template_id)->first();
        $project = Project::where('id', $request->project_id)->first();

        $checklist = Checklist::create([
            'name' => $template->name,
            'project_id' => $request->project_id,
            'user_id' => $request->user_id
        ]);

        foreach ($template->task_templates as $task_template) {
            Task::create([
                'name' => $task_template->name,
                'description' => $task_template->description,
                'done' => false,
                'checklist_id' => $checklist->id,
                'order' => Task::max('order') + 1,
            ]);
        }

        if (Auth::user()->can('update departments') && Auth::user()->can(PermissionNameEnum::PROJECT_MANAGEMENT->value)) {
            foreach ($template->departments as $department) {
                if (! $project->departments->contains($department)) {
                    $project->departments()->attach($department);
                }
            }

            $checklist->departments()->sync(
                collect($template->departments)
                    ->map(function ($department) {
                        return $department['id'];
                    })
            );
        } else {
            return response()->json(['error' => 'Not authorized to assign departments to a checklist.'], 403);
        }
    }

    /**
     * Default creation of a checklist without a template
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse|void
     */
    protected function createWithoutTemplate(Request $request)
    {
        $checklist = Checklist::create([
            'name' => $request->name,
            'project_id' => $request->project_id,
            'user_id' => $request->user_id
        ]);

        foreach ($request->tasks as $task) {
            Task::create([
                'name' => $task['name'],
                'description' => $task['description'],
                'done' => false,
                'deadline' => $task['deadline_dt_local'],
                'checklist_id' => $checklist->id,
                'order' => Task::max('order') + 1,
            ]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Checklist  $checklist
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function show(Checklist $checklist)
    {
        return inertia('Checklists/Show', [
            'checklist' => new ChecklistShowResource($checklist),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Checklist  $checklist
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function edit(Checklist $checklist)
    {
        return inertia('Checklists/Edit', [
            'checklist' => new ChecklistShowResource($checklist),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Checklist  $checklist
     */
    public function update(ChecklistUpdateRequest $request, Checklist $checklist)
    {
        $checklist->fill($request->data());

        $checklist->save();

        if ($request->get('tasks')) {
            $checklist->tasks()->delete();
            $checklist->tasks()->createMany($request->tasks);
        }

        if ($request->missing('assigned_user_ids')) {
            return Redirect::back()->with('success', 'Checklist updated');
        }

        // User Select
        $checklist->users()->sync($request->assigned_user_ids);
        $tasksInChecklist = $checklist->tasks()->get();
        foreach ($tasksInChecklist as $taskInChecklist){
            $taskInChecklist->task_users()->syncWithoutDetaching($request->assigned_user_ids);
        }
        /*$departmentIds = collect($request->get('assigned_department_ids'));
        if ($departmentIds->isNotEmpty()) {
            $syncedDepartmentIds = $checklist->project->departments()->pluck('departments.id');
            $checklist->project->departments()
                ->syncWithoutDetaching($departmentIds->whereNotIn('id', $syncedDepartmentIds));
        }*/

        $this->history = new HistoryController('App\Models\Project');
        $this->history->createHistory($checklist->project_id, 'Checkliste ' . $checklist->name . ' geändert');
        //$checklist->departments()->sync($departmentIds);

        return Redirect::back()->with('success', 'Checklist updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Checklist  $checklist
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Checklist $checklist, HistoryService $historyService)
    {
        $this->history = new HistoryController('App\Models\Project');
        $this->history->createHistory($checklist->project_id, 'Checkliste ' . $checklist->name . ' gelöscht');
        $checklist->delete();
        $historyService->checklistUpdated($checklist);

        return Redirect::back()->with('success', 'Checklist deleted');
    }
}
