<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\MissingValue;
use Illuminate\Support\Facades\Auth;

/**
 * @mixin \App\Models\Project
 */
class ProjectIndexResource extends JsonResource
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
        $projectHistory = $this->project_histories->sortByDesc('created_at');

        return [
            'resource' => class_basename($this),
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'number_of_participants' => $this->number_of_participants,
            'cost_center' => $this->cost_center,
            'sector' => $this->sector,
            'category' => $this->category,
            'genre' => $this->genre,
            'curr_user_is_related' => $this->users->contains(Auth::id()),
            'users' => UserIndexResource::collection($this->users)->resolve(),
            'project_history' => ProjectHistoryResource::collection($projectHistory)->resolve(),
            'departments' => DepartmentIndexResource::collection($this->departments)->resolve(),
            'events' => $this->deleted_at ? new MissingValue() : $this->events,
        ];
    }
}
