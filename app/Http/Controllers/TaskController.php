<?php

namespace App\Http\Controllers;

use App\Enums\NotificationConstEnum;
use App\Enums\RoleNameEnum;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Resources\TaskIndexResource;
use App\Http\Resources\TaskShowResource;
use App\Models\Project;
use Artwork\Modules\Checklist\Models\Checklist;
use App\Models\MoneySourceTask;
use App\Models\Task;
use App\Models\User;
use App\Support\Services\HistoryService;
use App\Support\Services\NewHistoryService;
use App\Support\Services\NotificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Redirect;
use Inertia\Response;
use Inertia\ResponseFactory;
use stdClass;

class TaskController extends Controller
{
    protected ?stdClass $notificationData = null;

    protected ?NewHistoryService $history = null;

    public function __construct(protected NotificationService $notificationService)
    {
        $this->notificationData = new stdClass();
        $this->notificationData->event = new stdClass();
        $this->notificationData->type = NotificationConstEnum::NOTIFICATION_TASK_CHANGED;
    }

    public function create(): Response|ResponseFactory
    {
        return inertia('Tasks/Create');
    }

    public function indexOwnTasks(): Response|ResponseFactory
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
            }])->where('done', false)->get()
        ]);
    }

    public function store(StoreTaskRequest $request): JsonResponse|RedirectResponse
    {
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

        $this->history = new NewHistoryService(Project::class);
        $this->history->createHistory(
            $checklist->project_id,
            'Aufgabe ' . $request->name . ' zu ' . $checklist->name . ' hinzugefügt'
        );
        $this->createNotificationForAllChecklistUser($checklist);
        return Redirect::back()->with('success', 'Task created.');
    }

    private function createNotificationForAllChecklistUser(Checklist $checklist): void
    {
        $taskScheduling = new SchedulingController();
        $uniqueTaskUsers = [];
        if ($checklist->user_id === null) {
            $checklistUsers = $checklist->users()->get();
            foreach ($checklistUsers as $checklistUser) {
                $uniqueTaskUsers[$checklistUser->id] = $checklistUser->id;
            }
            foreach ($uniqueTaskUsers as $uniqueTaskUser) {
                $taskScheduling->create($uniqueTaskUser, 'TASK_ADDED', 'TASKS', $checklist->id);
            }
        } else {
            $taskScheduling->create(Auth::id(), 'TASK_ADDED', 'TASKS', $checklist->id);
        }
    }

    private function createTask(Request $request): void
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

        (new HistoryService())->taskUpdated($task);
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
            $this->history = new NewHistoryService('App\Models\Project');
            $this->history->createHistory(
                $checklist->project_id,
                'Aufgabe ' . $task->name . ' von ' . $checklist->name . ' geändert'
            );

            $this->createNotificationUpdateTask($task);
        }

        return Redirect::back()->with('success', 'Task updated');
    }

    private function createNotificationUpdateTask(Task $task): void
    {
        $taskUser = $task->checklist()->first()->user_id;
        $users = $task->task_users()->get();
        $taskScheduling = new SchedulingController();
        $uniqueTaskUsers = [];
        if ($taskUser === null) {
            foreach ($users as $user) {
                $uniqueTaskUsers[$user->id] = $user->id;
            }
            foreach ($uniqueTaskUsers as $uniqueTaskUser) {
                $taskScheduling->create($uniqueTaskUser, 'TASK_CHANGES', 'TASKS', $task->id);
            }
        } else {
            $taskScheduling->create($taskUser, 'TASK_CHANGES', 'TASKS', $task->id);
        }
    }

    public function updateOrder(Request $request, HistoryService $historyService): RedirectResponse
    {
        $firstTask = Task::findOrFail($request->tasks[0]['id']);

        foreach ($request->tasks as $task) {
            Task::findOrFail($task['id'])->update(['order' => $task['order']]);
        }
        $historyService->taskUpdated($firstTask);

        return Redirect::back();
    }

    public function destroy(Task $task): RedirectResponse
    {
        $checklist = $task->checklist()->first();
        $this->history = new NewHistoryService('App\Models\Project');
        $this->history->createHistory(
            $checklist->project_id,
            'Aufgabe ' . $task->name . ' von ' . $checklist->name . ' gelöscht'
        );
        $task->delete();
        return Redirect::back()->with('success', 'Task deleted');
    }
}
