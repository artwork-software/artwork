<?php

namespace App\Http\Resources;

use App\Enums\PermissionNameEnum;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResourceWithoutShifts extends JsonResource
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

        dd($this);
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
            'pivot_access_budget' => $this->pivot->access_budget,
            'pivot_is_manager' => $this->pivot->is_manager,
            'pivot_can_write' => $this->pivot->can_write,
            'pivot_delete_permission_users' => $this->pivot->delete_permission,
        ];
    }
}
