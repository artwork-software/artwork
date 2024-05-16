<?php

namespace Artwork\Modules\Task\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskIndexResource extends JsonResource
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
            'checklist' => $this->checklist,
            'project' => $this->checklist->project,
            'description' => $this->description,
            'deadline' => $this->deadline ? Carbon::parse($this->deadline)->format('d.m.Y, H:i') : null,
            'deadlineDate' => $this->deadline ? Carbon::parse($this->deadline)->format('Y-m-d') : null,
            'deadlineTime' => $this->deadline ? Carbon::parse($this->deadline)->format('H:i') : null,
            'deadline_dt_local' => $this->deadline ? Carbon::parse($this->deadline)->toDateTimeLocalString() : null,
            'order' => $this->order,
            'done' => $this->done,
            'done_by_user' => $this->user_who_done,
            'done_at' => Carbon::parse($this->done_at)->format('d.m.Y, H:i'),
            'done_at_dt_local' => Carbon::parse($this->done_at)->toDateTimeLocalString(),
            'users' => $this->task_users()->get()
        ];
    }
}
