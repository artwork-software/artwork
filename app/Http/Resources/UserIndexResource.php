<?php

namespace App\Http\Resources;

use App\Enums\PermissionNameEnum;
use Illuminate\Http\Resources\Json\JsonResource;

class UserIndexResource extends JsonResource
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
            'can_master' => $this->can_master,
            'shifts' => $this->getShiftsAttribute(),
            'display_name' => $this->getDisplayNameAttribute(),
            'type' => $this->getTypeAttribute(),
        ];
    }
}
