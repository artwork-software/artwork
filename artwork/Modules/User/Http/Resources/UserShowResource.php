<?php

namespace Artwork\Modules\User\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserShowResource extends JsonResource
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
            'description' => $this->description,
            'departments' => $this->departments,
            'position' => $this->position,
            'business' => $this->business,
            'phone_number' => $this->phone_number,
            'roles' => $this->getRoleNames(),
            'permissions' => $this->getAllPermissions()->pluck('name'),
            'temporary' => $this->temporary,
            'employStart' => $this->employStart,
            'employEnd' => $this->employEnd,
            'can_work_shifts' => $this->can_work_shifts,
            'work_name' => $this->work_name,
            'work_description' => $this->work_description,
            'weekly_working_hours' => $this->weekly_working_hours,
            'salary_per_hour' => $this->salary_per_hour,
            'salary_description' => $this->salary_description,
            'crafts' => $this->crafts,
            'language' => $this->language,
        ];
    }
}
