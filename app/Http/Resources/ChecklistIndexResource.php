<?php

namespace App\Http\Resources;

use App\Models\User;
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
            'private' => $this->user_id !== null,
            //determines if the checklist is already opened by default
            'showContent' => true,
            'tasks' => TaskIndexResource::collection($this->tasks->sortBy('order'))->resolve(),
            // only show departments on public checklists, not on private
            'users' => $this->user_id
                ? new MissingValue()
                : $this->users->map(fn (User $user) => [
                    'id' => $user->id,
                    'profile_photo_url' => $user->profile_photo_url,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                ])
        ];
    }
}
