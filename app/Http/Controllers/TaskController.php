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
use Illuminate\Support\Facades\Date;
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
        $own_tasks = new Collection();

        foreach (Checklist::all() as $checklist) {
            foreach ($checklist->departments as $department) {
                if ($department->users->contains(Auth::id())) {
                    foreach ($checklist->tasks as $task) {
                        if (!$own_tasks->contains($task)) {
                            $own_tasks->push($task);
                        }
                    }
                }
            }
            if ($checklist->user_id == Auth::id()) {
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
                'deadline' =>  Carbon::parse($task->deadline)->format('d.m.Y, H:i'),
                'deadline_dt_local' => Carbon::parse($task->deadline)->toDateTimeLocalString(),
                'done' => $task->done,
                'checklist' => $task->checklist,
                'project' => $task->checklist->project,
                'departments' => $task->checklist->departments,
                'done_by_user' => $task->user_who_done,
                'done_at' => Carbon::parse($task->done_at)->format('d.m.Y, H:i'),
                'done_at_dt_local' => Carbon::parse($task->done_at)->toDateTimeLocalString()
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

            $checklist->project->project_histories()->create([
                "user_id" => Auth::id(),
                "description" => "Aufgabe $request->name zur Checkliste $checklist->name"
            ]);

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
            'order' => Task::max('order') + 1,
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
                'done_by_user' => $task->user_who_done,
                'done_at' => Carbon::parse($task->done_at)->format('d.m.Y, H:i'),
                'done_at_dt_local' => Carbon::parse($task->done_at)->toDateTimeLocalString()
            ]
        ]);
    }

    private function get_task_status($name, $is_done) {
        if($is_done) {
            return "hat die Aufgabe $name abgehakt";
        } else {
            return "hat die Aufgabe $name auf noch nicht erledigt gesetzt";
        }
    }

    private function history_description_change($changed_field, $task, $original, $change): string
    {

        return match ($changed_field) {
            'name' => "Die Aufgabe $original wurde in $change umbenannt",
            'description' => "Kurzbeschreibung von Aufgabe $task->name wurde geändert",
            'deadline' => "Die Deadline der Aufgabe $task->name wurde geändert",
            'done' => $this->get_task_status($task->name, $change),
        };
    }

    private function history_description_removed($changed_field, $task): string
    {

        return match ($changed_field) {
            'description' => "Kurzbeschreibung von Aufgabe $task->name wurde entfernt",
        };

    }


    private function add_to_history($task): void {

        $original = $task->getOriginal();
        $changes = $task->getDirty();

        $changed_fields = array_keys($changes);

        foreach ($changed_fields as $change) {

            if($changes[$change] === null) {

                if($change != 'done_at' && $change != 'user_id') {
                    $task->checklist->project->project_histories()->create([
                        "user_id" => Auth::id(),
                        "description" => $this->history_description_removed($change, $task)
                    ]);
                }

            } else {

                if($change === 'deadline') {

                    if(Carbon::parse($original[$change])->notEqualTo(Carbon::parse($changes[$change]))) {
                        $task->checklist->project->project_histories()->create([
                            "user_id" => Auth::id(),
                            "description" => $this->history_description_change($change, $task, $original[$change], $changes[$change])
                        ]);
                    }
                } else if($change !== 'done_at' && $change !== 'user_id') {
                    $task->checklist->project->project_histories()->create([
                        "user_id" => Auth::id(),
                        "description" => $this->history_description_change($change, $task, $original[$change], $changes[$change])
                    ]);
                }


            }

        }
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
        $update_properties = $request->only('name', 'description', 'deadline', 'done', 'checklist_id');

        if($request->done == true) {
            $task->user_who_done()->associate(Auth::user());
            $task->done_at = Date::now();
        }
        if($request->done == false) {
            $task->user_id = null;
            $task->done_at = null;
        }

        $task->fill($update_properties);

        $this->add_to_history($task);

        $task->save();

        return Redirect::back()->with('success', 'Task updated');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Task $task
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateOrder(Request $request)
    {

        $firstTask = Task::findOrFail($request->tasks[0]['id']);

        foreach ($request->tasks as $task) {
            Task::findOrFail($task['id'])->update(['order' => $task['order']]);
        }

        $firstTask->checklist->project->project_histories()->create([
            "user_id" => Auth::id(),
            "description" => "Aufgabenanordnung in der Checkliste {$firstTask->checklist->name} geändert"
        ]);

        return Redirect::back();
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

        $task->checklist->project->project_histories()->create([
            "user_id" => Auth::id(),
            "description" => "Aufgabe $task->name aus der Checkliste {$task->checklist->name} entfernt"
        ]);
        return Redirect::back()->with('success', 'Task deleted');
    }
}
