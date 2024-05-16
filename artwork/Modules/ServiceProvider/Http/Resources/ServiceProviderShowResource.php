<?php

namespace Artwork\Modules\ServiceProvider\Http\Resources;

use Artwork\Modules\Craft\Models\Craft;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceProviderShowResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClass
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'profile_image' => $this->profile_image,
            'provider_name' => $this->provider_name,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'street' => $this->street,
            'zip_code' => $this->zip_code,
            'location' => $this->location,
            'note' => $this->note,
            'salary_per_hour' => $this->salary_per_hour,
            'salary_description' => $this->salary_description,
            'work_name' => $this->work_name,
            'work_description' => $this->work_description,
            'can_work_shifts' => $this->can_work_shifts,
            'assignedCrafts' => $this->assignedCrafts,
            'assignableCrafts' => Craft::query()->get()->filter(
                fn($craft) => !$this->assignedCrafts->pluck('id')->contains($craft->id)
            )->toArray(),
            'shiftQualifications' => $this->shiftQualifications,
            'contacts' => $this->contacts,
        ];
    }
}
