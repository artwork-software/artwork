<?php

namespace Artwork\Modules\EventType\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EventTypeResource extends JsonResource
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
            'name' => $this->name,
            'hex_code' => $this->hex_code,
            'project_mandatory' => $this->project_mandatory,
            'individual_name' => $this->individual_name,
            'abbreviation' => $this->abbreviation,
            'relevant_for_project_period' => $this->relevant_for_project_period,
            'verification_mode' => $this->verification_mode,
            'relevant_for_shift' => $this->relevant_for_shift,
            'relevant_for_inventory' => $this->relevant_for_inventory,
            'specific_verifier_id' => $this->specific_verifier_id,
            'users' => $this->verifiers->map(function ($user) {
                return [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'full_name' => $user->full_name,
                    'name' => $user->full_name,
                    'email' => $user->email,
                    'profile_photo_url' => $user->profile_photo_url,
                ];
            }),
        ];
    }
}
