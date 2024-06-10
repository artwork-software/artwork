<?php

namespace Artwork\Modules\Freelancer\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class FreelancerShiftPlanResource extends JsonResource
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
            'profile_photo_url' => $this->profile_image,
            'shifts' => $this->resource->loadShifts(),
            'assigned_craft_ids' => $this->getAssignedCraftIdsAttribute(),
            'shift_ids' => $this->getShiftIdsBetweenStartDateAndEndDate($this->startDate, $this->endDate),
            'shift_qualifications' => $this->shiftQualifications,
            'morph_class' => $this->morph_class,
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
