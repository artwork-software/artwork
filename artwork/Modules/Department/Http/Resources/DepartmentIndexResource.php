<?php

namespace Artwork\Modules\Department\Http\Resources;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Http\Resources\UserIndexResource;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Artwork\Modules\Department\Models\Department
 */

class DepartmentIndexResource extends JsonResource
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
            'svg_name' => $this->svg_name,
            'users' => $this->users->map(fn ($user) => [
                'resource' => class_basename($user),
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'profile_photo_url' => $user->profile_photo_url,
                'email' => $user->email,
                'departments' => $user->departments,
                'position' => $user->position,
                'business' => $user->business,
                'phone_number' => $user->phone_number,
                'project_management' => $user->can(PermissionEnum::PROJECT_MANAGEMENT->value),
                'display_name' => $user->getDisplayNameAttribute(),
                'type' => $user->getTypeAttribute(),
                'assigned_craft_ids' => $user->getAssignedCraftIdsAttribute(),
            ]),
            //'users' => UserIndexResource::collection($this->users)->resolve()
        ];
    }
}
