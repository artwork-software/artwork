<?php

namespace Artwork\Modules\User\Http\Resources;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class UserShiftPlanResource extends JsonResource
{
    public static $wrap = null;

    private Carbon $startDate;

    private Carbon $endDate;

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
            'shifts' => $this->loadShifts(),
            'display_name' => $this->getDisplayNameAttribute(),
            'type' => $this->getTypeAttribute(),
            'assigned_craft_ids' => $this->getAssignedCraftIdsAttribute(),
            'shift_ids' => $this->getShiftIdsBetweenStartDateAndEndDate($this->startDate, $this->endDate),
            'shift_qualifications' => $this->shiftQualifications,
        ];
    }

    public function setStartDate(Carbon $startDate): self
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function setEndDate(Carbon $endDate): self
    {
        $this->endDate = $endDate;
        return $this;
    }
}
