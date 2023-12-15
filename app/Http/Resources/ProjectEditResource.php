<?php

namespace App\Http\Resources;

use App\Models\Department;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectEditResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClass
    public function toArray($request): array
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
