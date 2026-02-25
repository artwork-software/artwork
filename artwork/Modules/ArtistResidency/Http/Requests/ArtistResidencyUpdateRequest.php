<?php

namespace Artwork\Modules\ArtistResidency\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ArtistResidencyUpdateRequest extends FormRequest
{
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
            'civil_name' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:20',
            'position' => 'nullable|string|max:255',
            'artist_id' => 'nullable|integer|exists:artists,id',
            'accommodation_id' => 'required|integer|exists:accommodations,id',
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
        ];
    }
}
