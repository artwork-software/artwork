<?php

namespace Artwork\Modules\Task\Http\Resources;

use Artwork\Modules\Project\Services\ProjectTabService;
use Artwork\Modules\Task\Models\Task;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShowOwnTasksResource extends JsonResource
{
    public static $wrap = null;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClass
    public function toArray(Request $request): array
    {

        return [
            'resource' => class_basename($this),
            'id' => $this->id,
            'name' => $this->name,
            'private' => $this->user_id !== null,
            'showContent' => true,
            'project' => [
                'id' => $this?->project?->id,
                'name' => $this?->project?->name,
            ],
            'checklist_tab_id' => $this->tab_id,
            'tasks' => $this->tasks->map(function (Task $task) {
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
                    'done_at' => Carbon::parse($task->done_at)->format('d.m.Y, H:i'),
                    'done_at_dt_local' => Carbon::parse($task->done_at)->toDateTimeLocalString(),
                    'users' => $task->task_users,
                    'formatted_dates' => $task->getFormattedDates(),
                ];
            }),
        ];
    }
}
