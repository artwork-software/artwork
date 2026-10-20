<?php

namespace Artwork\Modules\ServiceProvider\Http\Resources;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;

/**
 * @mixin \Artwork\Modules\ServiceProvider\Models\ServiceProvider
 */
class ServiceProviderShowResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClass
    public function toArray($request): array
    {
        $canViewTerms = Gate::allows('updateTerms', ServiceProvider::class);

        return [
            'id' => $this->id,
            'profile_image' => $this->profile_image,
            'profile_photo_url' => $this->profile_photo_url,
            'provider_name' => $this->provider_name,
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
            'assignableCrafts' => Craft::query()->with(['qualifications'])->get()->filter(
                fn($craft) => !$this->assignedCrafts->pluck('id')->contains($craft->id)
            )->toArray(),
            'shiftQualifications' => $this->shiftQualifications,
            'contacts' => $this->contacts,
            'type_of_provider' => $this->getAttribute('type_of_provider'),
        ];
    }
}
