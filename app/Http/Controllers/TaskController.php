<?php

namespace App\Http\Controllers;

use App\Enums\RoleNameEnum;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Resources\TaskIndexResource;
use App\Http\Resources\TaskShowResource;
use App\Models\MoneySourceTask;
use App\Models\Task;
use App\Models\User;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\ProjectTab\Services\ProjectTabService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Redirect;
use Inertia\Response;
use Inertia\ResponseFactory;

class TaskController extends Controller
{
    public function __construct(
        private readonly ChangeService $changeService,
        private readonly SchedulingController $schedulingController
    ) {
    }

    public function create(): Response|ResponseFactory
    {
        return inertia('Tasks/Create');
    }

    public function indexOwnTasks(ProjectTabService $projectTabService): Response|ResponseFactory
    {
        $tasks = Task::query()
            ->with(['checklist.project', 'checklist.users'])
            ->whereHas('checklist', fn(Builder $checklistBuilder) => $checklistBuilder
                ->where('user_id', Auth::id()))
            ->orWhereHas('task_users', function ($q): void {
                $q->where('user_id', Auth::id());
            })->get();

        return inertia('Tasks/OwnTasksManagement', [
            'tasks' => TaskShowResource::collection($tasks),
            'money_source_task' => MoneySourceTask::with(['money_source_task_users' => function ($query) {
                return $query->where('user_id', Auth::id());
            }])->where('done', false)->get(),
            'first_project_tasks_tab_id' => $projectTabService->findFirstProjectTabWithTasksComponent()?->id
        ]);
    }

    public function store(
        StoreTaskRequest $request
    ): JsonResponse|RedirectResponse {
        $checklist = Checklist::where('id', $request->checklist_id)->first();
        $authorized = false;
        $created = false;
        $user = User::where('id', Auth::id())->first();
        $isManager = $user->projects()->find($checklist->project->id)?->pivot?->is_manager === 1;
        $isAdmin = Auth::user()->hasRole(RoleNameEnum::ARTWORK_ADMIN->value);
        if ($isAdmin || $isManager) {
            $authorized = true;
            $this->createTask($request);
        } else {
            foreach ($checklist->users()->get() as $user) {
                if ($user->id === Auth::id()) {
                    if ($created === false) {
                        $authorized = true;
                        $this->createTask($request);
                        $created = true;
                    }
                }
            }
        }

        if (!$authorized) {
            return response()->json(['error' => 'Not authorized to create tasks on this checklist.'], 403);
        }

        $this->changeService->saveFromBuilder(
            $this->changeService
                ->createBuilder()
                ->setModelClass(Project::class)
                ->setModelId($checklist->project_id)
                ->setTranslationKey('Task added to')
                ->setTranslationKeyPlaceholderValues([
                    $request->name,
                    $checklist->name
                ])
        );

        $this->createNotificationForAllChecklistUser($checklist);

        return Redirect::back();
    }

    private function createNotificationForAllChecklistUser(Checklist $checklist): void
    {
        $uniqueTaskUsers = [];
        if ($checklist->user_id === null) {
            $checklistUsers = $checklist->users()->get();
            foreach ($checklistUsers as $checklistUser) {
                $uniqueTaskUsers[$checklistUser->id] = $checklistUser->id;
            }
            foreach ($uniqueTaskUsers as $uniqueTaskUser) {
                $this->schedulingController->create($uniqueTaskUser, 'TASK_ADDED', 'TASKS', $checklist->id);
            }
        } else {
            $this->schedulingController->create(Auth::id(), 'TASK_ADDED', 'TASKS', $checklist->id);
        }
    }

    private function createTask(StoreTaskRequest $request): void
    {
        $task = Task::create([
            'name' => $request->name,
            'description' => $request->description,
            'deadline' => $request->deadline,
            'done' => false,
            'order' => Task::max('order') + 1,
            'checklist_id' => $request->checklist_id
        ]);

        if (!$request->private) {
            if (!empty($request->users)) {
                $task->task_users()->sync(collect($request->users));
            }
        }

        $checklist = Checklist::find($request->checklist_id);
        $checklistUsers = $checklist->users()->get();

        $task->task_users()->syncWithoutDetaching(collect($checklistUsers)->pluck('id'));
    }

    public function edit(Task $task): Response|ResponseFactory
    {
        return inertia('Tasks/Edit', [
            'task' => new TaskIndexResource($task),
        ]);
    }

    public function update(Request $request, Task $task): RedirectResponse
    {
        $update_properties = $request->only('name', 'description', 'deadline', 'done', 'checklist_id');
        $task->user_id = null;
        $task->done_at = null;
        if (!empty($request->done)) {
            if ($request->done === true) {
                $task->user_who_done()->associate(Auth::user());
                $task->done_at = Date::now();
            }
        }

        $task->fill($update_properties);
        $task->save();

        if (!$request->private && !empty($request->users)) {
            $task->task_users()->sync(collect($request->users));
        }

        if ($checklist = $task->checklist()->first()) {
            $this->changeService->saveFromBuilder(
                $this->changeService
                    ->createBuilder()
                    ->setModelClass(Project::class)
                    ->setModelId($checklist->project_id)
                    ->setTranslationKey('Task changed from')
                    ->setTranslationKeyPlaceholderValues([
                        $task->name,
                        $checklist->name
                    ])
            );

            $this->createNotificationUpdateTask($task);
        }

        return Redirect::back();
    }

    private function createNotificationUpdateTask(Task $task): void
    {
        $taskUser = $task->checklist()->first()->user_id;
        $users = $task->task_users()->get();
        $uniqueTaskUsers = [];
        if ($taskUser === null) {
            foreach ($users as $user) {
                $uniqueTaskUsers[$user->id] = $user->id;
            }
            foreach ($uniqueTaskUsers as $uniqueTaskUser) {
                $this->schedulingController->create($uniqueTaskUser, 'TASK_CHANGES', 'TASKS', $task->id);
            }
        } else {
            $this->schedulingController->create($taskUser, 'TASK_CHANGES', 'TASKS', $task->id);
        }
    }

    public function updateOrder(Request $request): RedirectResponse
    {
        $firstTask = Task::findOrFail($request->tasks[0]['id']);

        foreach ($request->tasks as $task) {
            Task::findOrFail($task['id'])->update(['order' => $task['order']]);
        }

        return Redirect::back();
    }

    public function destroy(Task $task): RedirectResponse
    {
        $checklist = $task->checklist()->first();

        $this->changeService->saveFromBuilder(
            $this->changeService
                ->createBuilder()
                ->setModelClass(Project::class)
                ->setModelId($checklist->project_id)
                ->setTranslationKey('Task deleted from')
                ->setTranslationKeyPlaceholderValues([
                    $task->name,
                    $checklist->name
                ])
        );

        $task->forceDelete();

        return Redirect::back();
    }
}
