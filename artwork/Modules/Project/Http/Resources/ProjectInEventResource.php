<?php

namespace Artwork\Modules\Project\Http\Resources;

use Artwork\Modules\Department\Http\Resources\DepartmentIndexResource;
use Artwork\Modules\User\Http\Resources\UserIndexResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin \Artwork\Modules\Project\Models\Project
 */
class ProjectInEventResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClass
    public function toArray($request): array
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
            'isMemberOfADepartment' => $this->departments
                ->contains(fn ($department) => $department->users->contains(Auth::user())),
        ];
    }
}
