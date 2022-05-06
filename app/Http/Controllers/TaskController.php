<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRequest;
use App\Models\Checklist;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

class TaskController extends Controller
{
    /**
     * Show the form for creating a new resource.
     *
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function create()
    {
        return inertia('Tasks/Create');
    }

    public function index_own()
    {
        $user = Auth::user();
        $own_tasks = new Collection();

        foreach (Checklist::all() as $checklist) {
            foreach ($checklist->departments as $department) {
                if ($department->users->contains($user->id)) {
                    foreach ($checklist->tasks as $task) {
                        if (!$own_tasks->contains($task)) {
                            $own_tasks->push($task);
                        }
                    }
                }
            }
            if ($checklist->user_id == $user->id) {
                foreach ($checklist->tasks as $task) {
                    if (!$own_tasks->contains($task)) {
                        $own_tasks->push($task);
                    }
                }
            }
        }

        return inertia('Tasks/OwnTasksManagement', [
            'tasks' => $own_tasks->map(fn($task) => [
                'id' => $task->id,
                'name' => $task->name,
                'description' => $task->description,
                'deadline' => $task->deadline,
                'done' => $task->done,
                'checklist' => $task->checklist,
                'project' => $task->checklist->project,
                'departments' => $task->checklist->departments
            ])
        ]);

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
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
        }
        else {
            foreach ($checklist->departments as $department) {
                if ($department->users->contains(Auth::id())) {
                    if($created == false) {
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
        Task::create([
            'name' => $request->name,
            'description' => $request->description,
            'deadline' => $request->deadline,
            'done' => false,
            'checklist_id' => $request->checklist_id
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\Task $task
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function edit(Task $task)
    {
        return inertia('Tasks/Edit', [
            'task' => [
                'name' => $task->name,
                'description' => $task->description,
                'deadline' => $task->deadline,
                'done' => $task->done,
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Task $task
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Task $task)
    {
        $task->update($request->only('name', 'description', 'deadline', 'done', 'checklist_id'));

        return Redirect::back()->with('success', 'Task updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Task $task
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Task $task)
    {
        $task->delete();
        return Redirect::back()->with('success', 'Task deleted');
    }
}
