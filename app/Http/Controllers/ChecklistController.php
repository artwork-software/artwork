<?php

namespace App\Http\Controllers;

use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Checklist\Http\Requests\ChecklistUpdateRequest;
use Artwork\Modules\Checklist\Http\Resources\ChecklistShowResource;
use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Checklist\Services\ChecklistService;
use Artwork\Modules\Checklist\Models\ChecklistTemplate;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Task\Http\Requests\DoneOrUndoneTaskRequest;
use Artwork\Modules\Task\Models\Task;
use Artwork\Modules\Task\Services\TaskService;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Response;
use Inertia\ResponseFactory;

class ChecklistController extends Controller
{
    public function __construct(
        private readonly ChecklistService $checklistService,
        private readonly ChangeService $changeService,
        private readonly TaskService $taskService,
        private readonly AuthManager $authManager
    ) {
        $this->authorizeResource(Checklist::class);
    }

    public function create(): ResponseFactory
    {
        return inertia('Checklists/Create');
    }

    /**
     * @throws AuthorizationException
     */
    public function store(Request $request): RedirectResponse
    {
        if ($request->integer('project_id')) {
            $this->authorize('createProperties', Project::find($request->project_id));
        }

        //Check whether checklist should be created on basis of a template
        if ($request->integer('template_id')) {
            $this->createFromTemplate($request);
        } else {
            $this->createWithoutTemplate($request);
        }

        if ($request->integer('project_id')) {
            $this->changeService->saveFromBuilder(
                $this->changeService
                    ->createBuilder()
                    ->setModelClass(Project::class)
                    ->setModelId($request->project_id)
                    ->setTranslationKey('Checklist added')
                    ->setTranslationKeyPlaceholderValues([$request->name])
            );
        }
        return Redirect::back();
    }

    protected function createFromTemplate(Request $request): void
    {
        $template = ChecklistTemplate::where('id', $request->template_id)->first();


        $checklist = Checklist::create([
            'name' => $template->name,
            'project_id' => $request->project_id,
            'user_id' => $this->authManager->id(),
            'tab_id' => $request->tab_id ?? null,
            'private' => $request->private,

        ]);

        foreach ($template->task_templates as $task_template) {
            $task = Task::create([
                'name' => $task_template->name,
                'description' => $task_template->description,
                'done' => false,
                'checklist_id' => $checklist->id,
                'order' => Task::max('order') + 1,
                'deadline' => Carbon::now()->addDays(3)
            ]);
            $task->task_users()->sync(
                $template->users->map(function ($user) {
                    return $user['id'];
                })
            );
        }

        if ($checklist->hasProject()) {
            $project = $checklist->project;
            $userIds = $checklist->users->pluck('id');
            foreach ($userIds as $id) {
                if (!$project->users->contains($id)) {
                    $project->users()->attach($id);
                }
            }
        }

        $checklist->users()->sync(
            collect($template->users)
                ->map(function ($user) {
                    return $user['id'];
                })
        );
    }

    protected function createWithoutTemplate(Request $request): void
    {
        $checklist = Checklist::create([
            'name' => $request->name,
            'project_id' => $request->project_id,
            'user_id' => $this->authManager->id(),
            'tab_id' => $request->tab_id ?? null,
            'private' => $request->private,
        ]);

        if ($request->has('tasks')) {
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

        if ($checklist->hasProject()) {
            $project = $checklist->project;
            if (!$project->users->contains($this->authManager->id())) {
                $project->users()->attach($this->authManager->id());
            }
        }

        $checklist->users()->attach($this->authManager->id());
    }

    public function show(Checklist $checklist): Response|ResponseFactory
    {
        return inertia('Checklists/Show', [
            'checklist' => new ChecklistShowResource($checklist),
        ]);
    }

    public function edit(Checklist $checklist): Response|ResponseFactory
    {
        return inertia('Checklists/Edit', [
            'checklist' => new ChecklistShowResource($checklist),
        ]);
    }

    public function update(
        ChecklistUpdateRequest $request,
        Checklist $checklist,
        TaskService $taskService
    ): RedirectResponse {

        $setTabId = null;
        if ($request->tab_id === null && $checklist->tab_id !== null) {
            $setTabId = $checklist->tab_id;
        }

        $this->checklistService->updateByRequest($checklist, $request, $taskService);

        if ($setTabId !== null) {
            $checklist->tab_id = $setTabId;
            $checklist->save();
        }
        if ($request->missing('assigned_user_ids')) {
            return Redirect::back();
        }

        $this->checklistService->assignUsersById($checklist, $request->assigned_user_ids ?? []);

        if ($checklist->hasProject()) {
            $this->changeService->saveFromBuilder(
                $this->changeService
                    ->createBuilder()
                    ->setModelClass(Project::class)
                    ->setModelId($checklist->project_id)
                    ->setTranslationKey('Checklist modified')
                    ->setTranslationKeyPlaceholderValues([$checklist->name])
            );
        }
        return Redirect::back();
    }

    public function destroy(
        Checklist $checklist
    ): RedirectResponse {
        $checklist->tasks->each(function (Task $task): void {
            $task->forceDelete();
        });
        $checklist->users()->detach();
        $checklist->forceDelete();

        if ($checklist->hasProject()) {
            $this->changeService->saveFromBuilder(
                $this->changeService
                    ->createBuilder()
                    ->setModelClass(Project::class)
                    ->setModelId($checklist->project_id)
                    ->setTranslationKey('Checklist removed')
                    ->setTranslationKeyPlaceholderValues([$checklist->name])
            );
        }

        return Redirect::back();
    }

    public function duplicate(Checklist $checklist): RedirectResponse
    {
        $newChecklist = $this->checklistService->duplicate(
            $checklist
        );

        $newChecklist->save();

        $this->taskService->duplicateTasksByChecklist(
            $checklist,
            $newChecklist
        );


        //$newChecklist->users()->sync($checklist->users->pluck('id'));

        if ($newChecklist->hasProject()) {
            $project = $checklist->project;
            if (!$project->users->contains($this->authManager->id())) {
                $project->users()->attach($this->authManager->id());
            }
            $this->changeService->saveFromBuilder(
                $this->changeService
                    ->createBuilder()
                    ->setModelClass(Project::class)
                    ->setModelId($newChecklist->project_id)
                    ->setTranslationKey('Checklist duplicated')
                    ->setTranslationKeyPlaceholderValues([$newChecklist->name])
            );
        }

        $newChecklist->users()->sync($checklist->users->pluck('id'));

        return Redirect::back();
    }

    public function doneOrUndoneAllTasks(
        Checklist $checklist,
        DoneOrUndoneTaskRequest $request
    ): RedirectResponse {
        $this->taskService->doneOrUndoneAllTasks(
            $checklist,
            $request->boolean('done'),
            $this->authManager->id()
        );
        return Redirect::back();
    }
}
