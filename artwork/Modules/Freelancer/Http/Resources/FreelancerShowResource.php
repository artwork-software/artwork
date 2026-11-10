<?php

namespace Artwork\Modules\Freelancer\Http\Resources;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;

/**
 * @mixin \Artwork\Modules\Freelancer\Models\Freelancer
 */
class FreelancerShowResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClass
    public function toArray($request): array
    {
        $canViewTerms = Gate::allows('updateTerms', Freelancer::class);

        return [
            'id' => $this->id,
            'position' => $this->position,
            'business' => $this->business,
            'profile_image' => $this->profile_image,
            'profile_photo_url' => $this->profile_photo_url,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'street' => $this->street,
            'zip_code' => $this->zip_code,
            'location' => $this->location,
            // Gage und Notiz nur für Personen, die die Konditionen bearbeiten dürfen (nicht jede Dienstplan-Sicht).
            'note' => $canViewTerms ? $this->note : null,
            'salary_per_hour' => $canViewTerms ? $this->salary_per_hour : null,
            'salary_description' => $canViewTerms ? $this->salary_description : null,
            'work_name' => $this->work_name,
            'work_description' => $this->work_description,
            'can_work_shifts' => $this->can_work_shifts,
            'assignedCrafts' => $this->assignedCrafts->load('qualifications'),
            'assignableCrafts' => Craft::query()->get()->filter(
                fn($craft) => !$this->assignedCrafts->pluck('id')->contains($craft->id)
            )->toArray(),
            'shiftQualifications' => $this->shiftQualifications
        ];
    }
}
