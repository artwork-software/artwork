<?php

namespace App\Http\Resources;

use App\Enums\PermissionNameEnum;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResourceWithoutShifts extends JsonResource
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
            'project_management' => $this->can(PermissionNameEnum::PROJECT_MANAGEMENT->value),
            'can_master' => $this->hasMasterShiftQualification(),
            'pivot_access_budget' => (bool)$this->pivot->access_budget,
            'pivot_is_manager' => (bool)$this->pivot->is_manager,
            'pivot_can_write' => (bool)$this->pivot->can_write,
            'pivot_delete_permission' => (bool)$this->pivot->delete_permission,
        ];
    }
}
