<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\MissingValue;

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
        $projectHistory = $this->project_histories()
            ->with('user')
            ->orderByDesc('created_at')
            ->get();

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
            'users' => UserIndexResource::collection($this->users)->resolve(),
            'project_history' => ProjectHistoryResource::collection($projectHistory)->resolve(),
            'departments' => DepartmentIndexResource::collection($this->departments)->resolve(),
            'events' => $this->deleted_at ? new MissingValue() : $this->events,
        ];
    }
}
