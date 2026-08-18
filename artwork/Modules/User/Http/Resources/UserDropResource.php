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
            'assigned_craft_ids' => $this->assignedCrafts->pluck('id'),
            // Die Relation ist über WorkerEagerLoadConfig bereits eager geladen —
            // shiftQualifications()->get() hätte sie ignoriert und pro Person eine
            // eigene Query ausgelöst (im Projekt-Schicht-Tab 186 Stück).
            'shift_qualifications' => $this->relationLoaded('shiftQualifications')
                ? $this->shiftQualifications
                : $this->shiftQualifications()->get(
                    ['shift_qualifications.id', 'name', 'available']
                ),
            'formatted_vacation_days' => $this->getFormattedVacationDays(),
        ];
    }
}
