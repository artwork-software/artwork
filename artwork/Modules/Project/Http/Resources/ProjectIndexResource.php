<?php

namespace Artwork\Modules\Project\Http\Resources;

use Artwork\Modules\Department\Http\Resources\DepartmentIndexResource;
use Artwork\Modules\User\Http\Resources\UserIndexResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\MissingValue;
use Illuminate\Support\Facades\Auth;

/**
 * @mixin \Artwork\Modules\Project\Models\Project
 */
class ProjectIndexResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClass
    public function toArray($request): array
    {
        $historyArray = [];
        $historyComplete = $this->historyChanges()->all();

        foreach ($historyComplete as $history) {
            $historyArray[] = [
                'changes' => json_decode($history->changes),
                'created_at' => $history->created_at->diffInHours() < 24
                    ? $history->created_at->diffForHumans()
                    : $history->created_at->format('d.m.Y, H:i'),
            ];
        }

        return [
            'resource' => class_basename($this),
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'number_of_participants' => $this->number_of_participants,
            'cost_center' => $this->costCenter,
            'sector' => $this->sector,
            'category' => $this->category,
            'genre' => $this->genre,
            'curr_user_is_related' => $this->users->contains(Auth::id()),
            'users' => UserIndexResource::collection($this->users)->resolve(),
            'project_history' => $historyArray,
            'departments' => DepartmentIndexResource::collection($this->departments)->resolve(),
            'events' => $this->deleted_at ? new MissingValue() : $this->events,
        ];
    }
}
