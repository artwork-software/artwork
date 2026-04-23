<?php

namespace Artwork\Modules\ArtistResidency\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ArtistResidencyUpdateRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'breakfast_deduction_per_day' => $this->input('breakfast_deduction_per_day') ?? 0,
            'breakfast_count' => $this->input('breakfast_count') ?? 0,
            'additional_daily_allowance' => $this->input('additional_daily_allowance') ?? 0,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id' => 'required|integer|exists:artist_residencies,id',
            'name' => 'nullable|string|max:255',
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:50',
            'position' => 'nullable|string|max:255',
            'artist_id' => 'nullable|integer|exists:artists,id',
            'artist_crm_contact_id' => 'nullable|integer|exists:crm_contacts,id',
            'crm_property_values' => 'nullable|array',
            'crm_property_values.*' => 'nullable|string',
            'accommodation_id' => 'nullable|integer|exists:accommodations,id',
            'accommodation_crm_contact_id' => 'nullable|integer|exists:crm_contacts,id',
            'project_id' => 'required|integer|exists:projects,id',
            'arrival_date' => 'nullable|date',
            'arrival_time' => 'nullable|date_format:H:i',
            'departure_date' => 'nullable|date|after_or_equal:arrival_date',
            'departure_time' => 'nullable|date_format:H:i',
            'type_of_room' => 'nullable|integer|exists:accommodation_room_types,id',
            'cost_per_night' => 'required|numeric|min:0',
            'daily_allowance' => 'required|numeric|min:0',
            'additional_daily_allowance' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'breakfast_count' => 'nullable|integer|min:0',
            'breakfast_deduction_per_day' => 'nullable|numeric|min:0',
            'days' => 'required|integer|min:0',
            'do_not_save_artist' => 'nullable|boolean',
            'sync_crm_changes' => 'nullable|boolean',
        ];
    }
}
