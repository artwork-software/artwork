<?php

namespace Artwork\Modules\User\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserDropResource extends JsonResource
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
            'can_work_shifts' => $this->can_work_shifts,
            'assigned_crafts_ids' => $this->assignedCrafts->pluck('id'),
            'shift_qualifications' => $this->shiftQualifications()->get(['id', 'name', 'available']),
            'formatted_vacation_days' => $this->getFormattedVacationDays(),
        ];
    }
}
