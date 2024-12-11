<?php

namespace Artwork\Modules\ArtistResidency\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ArtistResidencyCreateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'civil_name' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:20',
            'position' => 'nullable|string|max:255',
            'service_provider_id' => 'required|integer|exists:service_providers,id',
            'project_id' => 'required|integer|exists:projects,id',
            'arrival_date' => 'nullable|date',
            'arrival_time' => 'nullable|date_format:H:i:s',
            'departure_date' => 'nullable|date|after_or_equal:arrival_date',
            'departure_time' => 'nullable|date_format:H:i:s',
            'type_of_room' => 'nullable|string|max:255',
            'cost_per_night' => 'required|numeric|min:0',
            'daily_allowance' => 'required|numeric|min:0',
            'additional_daily_allowance' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'days' => 'required|integer|min:0',
        ];
    }
}