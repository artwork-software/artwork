<?php

namespace App\Http\Resources;

use App\Models\Department;
use App\Models\Task;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Checklist
 */
class ChecklistShowResource extends JsonResource
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
            'tasks' => $this->tasks->map(fn (Task $task) => [
                'id' => $task->id,
                'name' => $task->name,
                'description' => $task->description,
                'deadline' => $task->deadline,
                'done' => $task->done,
            ]),
            'departments' => $this->departments->map(fn (Department $department) => [
                'id' => $department->id,
                'name' => $department->first_name,
                'svg_name' => $department->svg_name,
            ])
        ];
    }
}
