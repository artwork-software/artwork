<?php

namespace App\Http\Resources;

use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\MissingValue;

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
            'private' => $this->user_id !== null,
            'showContent' => true,
            'tasks' => $this->tasks->map(fn (Task $task) => [
                'id' => $task->id,
                'name' => $task->name,
                'description' => $task->description,
                'deadline' => $task->deadline ? Carbon::parse($task->deadline)->format('d.m.Y, H:i') : null,
                'deadlineDate' => $task->deadline ? Carbon::parse($task->deadline)->format('Y-m-d') : null,
                'deadlineTime' => $task->deadline ? Carbon::parse($task->deadline)->format('H:i') : null,
                'deadline_dt_local' => $task->deadline ? Carbon::parse($task->deadline)->toDateTimeLocalString() : null,
                'order' => $task->order,
                'done' => $task->done,
                'done_by_user' => $task->user_who_done,
                'done_at' => Carbon::parse($task->done_at)->format('d.m.Y, H:i'),
                'done_at_dt_local' => Carbon::parse($task->done_at)->toDateTimeLocalString(),
                'users' => $task->task_users
            ]),
        ];
    }
}
