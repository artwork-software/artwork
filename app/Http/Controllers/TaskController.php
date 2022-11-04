<?php

namespace App\Http\Controllers;

use App\Enums\NotificationConstEnum;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Resources\TaskIndexResource;
use App\Http\Resources\TaskShowResource;
use App\Models\Checklist;
use App\Models\Task;
use App\Models\User;
use App\Support\Services\HistoryService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Redirect;

class TaskController extends Controller
{
    protected ?NotificationController $notificationController = null;
    protected ?\stdClass $notificationData = null;

    public function __construct()
    {
        $this->notificationController = new NotificationController();
        $this->notificationData = new \stdClass();
        $this->notificationData->event = new \stdClass();
        $this->notificationData->type = NotificationConstEnum::NOTIFICATION_EVENT;
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function create()
    {
        return inertia('Tasks/Create');
    }

    public function indexOwnTasks()
    {
        $tasks = Task::query()
            ->with(['checklist.project', 'checklist.departments.users'])
            ->whereHas('checklist', fn (Builder $checklistBuilder) => $checklistBuilder
                ->where('user_id', Auth::id())
            )
            ->orWhereHas('checklistDepartments', fn (Builder $departmentBuilder) => $departmentBuilder
                ->whereHas('users', fn (Builder $userBuilder) => $userBuilder
                    ->where('users.id', Auth::id()))
            )
            ->get();

        return inertia('Tasks/OwnTasksManagement', [
            'tasks' => TaskShowResource::collection($tasks),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function store(StoreTaskRequest $request)
    {
        $checklist = Checklist::where('id', $request->checklist_id)->first();
        $authorized = false;
        $created = false;
        $user = User::where('id', Auth::id())->first();

        if (Auth::user()->hasRole('admin')
            || $user->projects()->find($checklist->project->id)->pivot->is_admin == 1
            || $user->projects()->find($checklist->project->id)->pivot->is_manager == 1
        ) {
            $authorized = true;
            $this->createTask($request);
        } else {
            foreach ($checklist->departments as $department) {
                if ($department->users->contains(Auth::id())) {
                    if ($created == false) {
                        $authorized = true;
                        $this->createTask($request);
                        $created = true;
                    }
                }
            }
        }

        if ($authorized == true) {
            return Redirect::back()->with('success', 'Task created.');
        } else {
            return response()->json(['error' => 'Not authorized to create tasks on this checklist.'], 403);
        }
    }

    protected function createTask(Request $request)
    {
        $task = Task::create([
            'name' => $request->name,
            'description' => $request->description,
            'deadline' => $request->deadline,
            'done' => false,
            'order' => Task::max('order') + 1,
            'checklist_id' => $request->checklist_id
        ]);

        (new HistoryService())->taskUpdated($task);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Task  $task
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function edit(Task $task)
    {
        return inertia('Tasks/Edit', [
            'task' => new TaskIndexResource($task),
        ]);
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Task  $task
     * @param  \App\Support\Services\HistoryService  $historyService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Task $task, HistoryService $historyService)
    {

        $update_properties = $request->only('name', 'description', 'deadline', 'done', 'checklist_id');

        if ($request->done == true) {
            $task->user_who_done()->associate(Auth::user());
            $task->done_at = Date::now();
        }
        if ($request->done == false) {
            $task->user_id = null;
            $task->done_at = null;
        }

        $task->fill($update_properties);
        $historyService->taskUpdated($task);
        $task->save();

        return Redirect::back()->with('success', 'Task updated');
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Support\Services\HistoryService  $historyService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateOrder(Request $request, HistoryService $historyService)
    {
        $firstTask = Task::findOrFail($request->tasks[0]['id']);

        foreach ($request->tasks as $task) {
            Task::findOrFail($task['id'])->update(['order' => $task['order']]);
        }
        $historyService->taskUpdated($firstTask);

        return Redirect::back();
    }

    /**
     * @param  \App\Models\Task  $task
     * @param  \App\Support\Services\HistoryService  $historyService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Task $task, HistoryService $historyService)
    {
        $task->delete();
        $historyService->taskUpdated($task);

        return Redirect::back()->with('success', 'Task deleted');
    }
}
