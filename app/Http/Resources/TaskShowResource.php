<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Task
 */
class TaskShowResource extends JsonResource
{
    public static $wrap = null;

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'resource' => class_basename($this),
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,

            'done' => (bool) $this->done_at,
            'humanDeadline' => $this->deadline
                ?->setTimezone($request->get('timezone', config('calendar.default_timezone')))
                ->format('d.d.Y'),
            'deadline' => $this->deadline->timestamp,
            'isDeadlineInFuture' => $this->deadline?->isFuture(),

            'isPrivate' => (bool) $this->checklist->user_id,
            'projectId' => $this->checklist->project->id,
            'projectName' => $this->checklist->project->name,
            'departments' => DepartmentIconResource::collection($this->checklist->departments),
            'checklistName' => $this->checklist->name,
            'checklistId' => $this->checklist->id,
        ];
    }
}
