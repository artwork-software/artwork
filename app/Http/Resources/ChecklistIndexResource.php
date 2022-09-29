<?php

namespace App\Http\Resources;

use App\Models\Department;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\MissingValue;

/**
 * @mixin \App\Models\Checklist
 */
class ChecklistIndexResource extends JsonResource
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
            //determines if the checklist is already opened by default
            'showContent' => true,
            'tasks' => TaskIndexResource::collection($this->tasks->sortBy('order'))->resolve(),
            // only show departments on public checklists, not on private
            'departments' => $this->user_id
                ? new MissingValue()
                : $this->departments->map(fn (Department $department) => [
                    'id' => $department->id,
                    'name' => $department->name,
                    'svg_name' => $department->svg_name,
                ])
        ];
    }
}
