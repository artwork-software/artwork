<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\MissingValue;

class ChecklistIndexResource extends JsonResource
{
    /**
     * @var null
     */
    public static $wrap = null;

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
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
