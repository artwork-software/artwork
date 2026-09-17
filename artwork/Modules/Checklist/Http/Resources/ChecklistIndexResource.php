<?php

namespace Artwork\Modules\Checklist\Http\Resources;

use Artwork\Modules\Task\Models\Task;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class ChecklistIndexResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClass
    public function toArray($request): array
    {
        return [
            'resource' => class_basename($this),
            'id' => $this->id,
            'name' => $this->name,
            'private' => $this->private,
            'showContent' => true,
            'users' => $this->users,
            'user_id' => $this->user_id,
            'hasProject' => $this->project_id !== null,
            'project' => $this->project ? [
                'id' => $this->project->id,
                'name' => $this->project->name,
            ] : null,
            'checklist_tab_id' => $this->tab_id,
            'tasks' => $this->orderedTasks()->map(function (Task $task) {
                return [
                    'id' => $task->id,
                    'name' => $task->name,
                    'description' => $task->description,
                    'deadline' => $task->deadline ? Carbon::parse($task->deadline)->format('d.m.Y, H:i') : null,
                    'deadlineDate' => $task->deadline ? Carbon::parse($task->deadline)->format('Y-m-d') : null,
                    'deadlineTime' => $task->deadline ? Carbon::parse($task->deadline)->format('H:i') : null,
                    'deadline_dt_local' => $task->deadline ?
                        Carbon::parse($task->deadline)->toDateTimeLocalString() : null,
                    'order' => $task->order,
                    'done' => $task->done,
                    'done_by_user' => $task->user_who_done,
                    'done_at' => $task->done_at ? Carbon::parse($task->done_at)->format('d.m.Y, H:i') : null,
                    'done_at_dt_local' => $task->done_at ?
                        Carbon::parse($task->done_at)->toDateTimeLocalString() : null,
                    'users' => $task->task_users,
                    'formatted_dates' => $task->getFormattedDates(),
                ];
            }),
        ];
    }

    /**
     * Aufgaben aus der bereits geladenen Relation (die Aufrufer laden `tasks.task_users`
     * eager); vorher fragte die Resource je Checkliste die Aufgaben neu ab und je Aufgabe
     * die erledigende Person nach. Ohne Vorladung: eine Query je Checkliste statt je Aufgabe.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Task>
     */
    private function orderedTasks(): \Illuminate\Database\Eloquent\Collection
    {
        if (!$this->relationLoaded('tasks')) {
            return $this->tasks()->with(['task_users', 'user_who_done'])->orderBy('order')->get();
        }

        $tasks = $this->tasks;
        $tasks->loadMissing(['task_users', 'user_who_done']);

        return $tasks->sortBy('order')->values();
    }
}
