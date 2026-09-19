<?php

namespace Artwork\Modules\User\Http\Resources;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\WorkTime\Services\WorkTimeCalculationService;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserShowResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClass
    public function toArray($request): array
    {
        $viewer = $request->user();
        $isSelf = $viewer?->is($this->resource) ?? false;
        $canViewPrivate = $isSelf || ($viewer?->can(PermissionEnum::CAN_VIEW_PRIVATE_USER_INFO->value) ?? false);
        $canViewTerms = $viewer?->can(PermissionEnum::MA_MANAGER->value) ?? false;

        return [
            'resource' => class_basename($this),
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'profile_photo_url' => $this->profile_photo_url,
            'email' => !$this->email_private || $canViewPrivate ? $this->email : null,
            'description' => $this->description,
            'departments' => $this->departments,
            'position' => $this->position,
            'business' => $this->business,
            'pronouns' => $this->pronouns,
            'phone_number' => !$this->phone_private || $canViewPrivate ? $this->phone_number : null,
            'roles' => $this->getRoleNames(),
            'permissions' => $this->getAllPermissions()->pluck('name'),
            'temporary' => $canViewTerms ? $this->temporary : null,
            'employStart' => $canViewTerms ? $this->employStart : null,
            'employEnd' => $canViewTerms ? $this->employEnd : null,
            'can_work_shifts' => $this->can_work_shifts,
            'work_name' => $this->work_name,
            'work_description' => $this->work_description,
            // Wochenstunden laut heute gültigem Arbeitszeitmuster (null ohne Muster); die Spalte
            // users.weekly_working_hours wird nicht mehr gepflegt und hier bewusst nicht ausgeliefert
            'weekly_working_hours' => $this->resource instanceof User
                ? app(WorkTimeCalculationService::class)->currentWeeklyHours($this->resource)
                : null,
            'salary_per_hour' => $canViewTerms ? $this->salary_per_hour : null,
            'salary_description' => $canViewTerms ? $this->salary_description : null,
            'crafts' => $this->crafts,
            'language' => $this->language,
            'email_private' => $this->email_private,
            'phone_private' => $this->phone_private,
            'use_chat' => $this->use_chat,
        ];
    }
}
