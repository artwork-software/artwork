<?php

namespace Artwork\Modules\User\Http\Resources;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Illuminate\Http\Resources\Json\JsonResource;

class UserWithoutShiftsResource extends JsonResource
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
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'profile_photo_url' => $this->profile_photo_url,
            'email' => $this->email,
            'departments' => $this->departments,
            'position' => $this->position,
            'business' => $this->business,
            'phone_number' => $this->phone_number,
            'project_management' => $this->can(PermissionEnum::PROJECT_MANAGEMENT->value),
            'pivot_access_budget' => (bool)$this->pivot?->access_budget,
            'pivot_is_manager' => (bool)$this->pivot?->is_manager,
            'pivot_can_write' => (bool)$this->pivot?->can_write,
            'pivot_delete_permission' => (bool)$this->pivot?->delete_permission,
            'pivot_roles' => (array)$this->pivot?->roles,
        ];
    }
}
