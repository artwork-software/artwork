<?php

namespace Artwork\Modules\ServiceProvider\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceProviderShiftPlanResource extends JsonResource
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
            'provider_name' => $this->provider_name,
            'profile_photo_url' => $this->profile_image,
            'shifts' => $this->loadShifts(),
            'assigned_craft_ids' => $this->getAssignedCraftIdsAttribute(),
            'shift_ids' => $this->getShiftIdsBetweenStartDateAndEndDate($this->startDate, $this->endDate),
            'shift_qualifications' => $this->shiftQualifications
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
