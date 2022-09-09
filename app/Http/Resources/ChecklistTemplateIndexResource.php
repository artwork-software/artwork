<?php

namespace App\Http\Resources;

use App\Models\Department;
use App\Models\TaskTemplate;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\ChecklistTemplate
 */
class ChecklistTemplateIndexResource extends JsonResource
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
            'user' => $this->user,
            'task_templates' => $this->task_templates->map(fn (TaskTemplate $taskTemplate) => [
                'id' => $taskTemplate->id,
                'name' => $taskTemplate->name,
                'description' => $taskTemplate->description,
                'done' => $taskTemplate->done,
            ]),
            'departments' => $this->departments->map(fn (Department $department) => [
                'id' => $department->id,
                'name' => $department->name,
                'svg_name' => $department->svg_name,
            ]),
            'updated_at' => $this->updated_at,
            'created_at' => $this->created_at->format('d.m.Y, H:i')
        ];
    }
}
