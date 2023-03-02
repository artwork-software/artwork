<?php

namespace App\Http\Controllers;

use App\Enums\NotificationConstEnum;
use App\Enums\RoleNameEnum;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Resources\TaskIndexResource;
use App\Http\Resources\TaskShowResource;
use App\Models\Checklist;
use App\Models\Department;
use App\Models\MoneySourceTask;
use App\Models\Task;
use App\Models\Scheduling;
use App\Models\User;
use App\Support\Services\HistoryService;
use Illuminate\Database\Eloquent\Builder;
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
    protected ?NotificationController $notificationController = null;
    protected ?stdClass $notificationData = null;
    protected ?HistoryController $history = null;

    public function __construct()
    {
        $this->notificationController = new NotificationController();
        $this->notificationData = new stdClass();
        $this->notificationData->event = new stdClass();
        $this->notificationData->type = NotificationConstEnum::NOTIFICATION_TASK_CHANGED;
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return Response|ResponseFactory
     */
    public function create()
    {
        return inertia('Tasks/Create');
    }

    public function indexOwnTasks()
    {
        $tasks = Task::query()
            ->with(['checklist.project', 'checklist.users'])
            ->whereHas('checklist', fn (Builder $checklistBuilder) => $checklistBuilder
                ->where('user_id', Auth::id())
            )
            ->orWhereHas('checklistUsers', fn (Builder $departmentBuilder) => $departmentBuilder
                ->whereHas('users', fn (Builder $userBuilder) => $userBuilder
                    ->where('users.id', Auth::id()))
            )
            ->orWhereHas('task_users', function ($q) {
               $q->where('user_id',  Auth::id());
        })->get();

        return inertia('Tasks/OwnTasksManagement', [
            'tasks' => TaskShowResource::collection($tasks),
            'money_source_task' => MoneySourceTask::with(['money_source_task_users' => function($query){
                return $query->where('user_id', Auth::id());
            }])->where('done', false)->get()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     */
    public function store(StoreTaskRequest $request)
    {
        $checklist = Checklist::where('id', $request->checklist_id)->first();
        $authorized = false;
        $created = false;
        $user = User::where('id', Auth::id())->first();

        if (Auth::user()->hasRole(RoleNameEnum::ARTWORK_ADMIN->value)
            || $user->projects()->find($checklist->project->id)->pivot->is_manager == 1
        ) {
            $authorized = true;
            $this->createTask($request);
        } else {
            foreach ($checklist->users()->get() as $user) {
                if ($user->id === Auth::id()) {
                    if ($created == false) {
                        $authorized = true;
                        $this->createTask($request);
                        $created = true;
                    }
                }
            }
        }

        if ($authorized == true) {
            $this->history = new HistoryController('App\Models\Project');
            $this->history->createHistory($checklist->project_id, 'Aufgabe ' . $request->name . ' zu ' . $checklist->name . ' hinzugefügt');
            $this->createNotificationForAllChecklistUser($checklist);
            return Redirect::back()->with('success', 'Task created.');
        } else {
            return response()->json(['error' => 'Not authorized to create tasks on this checklist.'], 403);
        }


    }

    /**
     * @param Checklist $checklist
     * @return void
     */
    private function createNotificationForAllChecklistUser(Checklist $checklist): void
    {
        $taskScheduling = new SchedulingController();
        $uniqueTaskUsers = [];
        if($checklist->user_id === null){
            $checklistUsers = $checklist->users()->get();
            foreach ($checklistUsers as $checklistUser){
                $uniqueTaskUsers[$checklistUser->id] = $checklistUser->id;
            }
            foreach ($uniqueTaskUsers as $uniqueTaskUser){
                $taskScheduling->create($uniqueTaskUser, 'TASK');
            }
        } else {
            $taskScheduling->create(Auth::id(), 'TASK');
        }
    }

    private function createTask(Request $request)
    {
        $task = Task::create([
            'name' => $request->name,
            'description' => $request->description,
            'deadline' => $request->deadline,
            'done' => false,
            'order' => Task::max('order') + 1,
            'checklist_id' => $request->checklist_id
        ]);

        $Checklist = Checklist::find($request->checklist_id);
        $checklistUsers = $Checklist->users()->get();

        $task->task_users()->syncWithoutDetaching(collect($checklistUsers)->pluck('id'));

        (new HistoryService())->taskUpdated($task);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Task $task
     * @return Response|ResponseFactory
     */
    public function edit(Task $task)
    {
        return inertia('Tasks/Edit', [
            'task' => new TaskIndexResource($task),
        ]);
    }

    /**
     * @param Request $request
     * @param Task $task
     * @return RedirectResponse
     */
    public function update(Request $request, Task $task)
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

        $task->save();


        $checklist = $task->checklist()->first();
        if($checklist !== null){
            $this->history = new HistoryController('App\Models\Project');
            $this->history->createHistory($checklist->project_id, 'Aufgabe ' . $task->name . ' von ' . $checklist->name . ' geändert');

            $this->createNotificationUpdateTask($task);
        }


        return Redirect::back()->with('success', 'Task updated');
    }

    /**
     * @param Task $task
     * @return void
     */
    private function createNotificationUpdateTask(Task $task): void
    {
        $taskUser = $task->checklist()->first()->user_id;
        $users = $task->task_users()->get();
        $taskScheduling = new SchedulingController();
        $uniqueTaskUsers = [];
        if($taskUser === null){
            foreach ($users as $user){
                $uniqueTaskUsers[$user->id] = $user->id;
            }
            foreach ($uniqueTaskUsers as $uniqueTaskUser){
                $taskScheduling->create($uniqueTaskUser, 'TASK_CHANGES', null, $task->id);
            }
        } else {
            $taskScheduling->create($taskUser, 'TASK_CHANGES', null, $task->id);
        }
    }

    /**
     * @param Request $request
     * @param HistoryService $historyService
     * @return RedirectResponse
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
     * @param Task $task
     * @param HistoryService $historyService
     * @return RedirectResponse
     */
    public function destroy(Task $task)
    {
        $checklist = $task->checklist()->first();
        $this->history = new HistoryController('App\Models\Project');
        $this->history->createHistory($checklist->project_id, 'Aufgabe ' . $task->name . ' von ' . $checklist->name . ' gelöscht');
        $task->delete();
        return Redirect::back()->with('success', 'Task deleted');
    }
}
