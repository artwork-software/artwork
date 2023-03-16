<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin \App\Models\Project
 */
class ProjectInEventResource extends JsonResource
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
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'is_group' => $this->is_group,
            'group' => $this->groups,
            'key_visual_path' => Storage::disk('public')->url($this->key_visual_path),
            'state' => $this->state()->first(),
            'users' => UserIndexResource::collection($this->users)->resolve(),
            'departments' => DepartmentIndexResource::collection($this->departments)->resolve(),

            'isMemberOfADepartment' => $this->departments->contains(fn ($department) => $department->users->contains(Auth::user())),
        ];
    }
}
