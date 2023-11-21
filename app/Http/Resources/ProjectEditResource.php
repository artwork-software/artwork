<?php

namespace App\Http\Resources;

use Artwork\Modules\Department\Models\Department;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Artwork\Modules\Project\Models\Project
 */
class ProjectEditResource extends JsonResource
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
            'number_of_participants' => $this->number_of_participants,
            'cost_center' => $this->cost_center,
            'sectors' => $this->sectors,
            'categories' => $this->categories,
            'genres' => $this->genres,
            'users' => UserIndexResource::collection($this->users)->resolve(),
            'departments' => $this->departments->map(fn (Department $department) => [
                'id' => $department->id,
                'name' => $department->name,
                'svg_name' => $department->svg_name,
                'users' => UserIndexResource::collection($department->users)->resolve(),
            ]),
        ];
    }
}
