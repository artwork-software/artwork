<?php

namespace Artwork\Modules\Accommodation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAccommodationRequest extends FormRequest
{
    /**
     * Autorisierung VOR der Validierung, sonst antwortet ein fehlendes Recht mit 422 statt 403.
     */
    public function authorize(): bool
    {
        $accommodation = $this->route('accommodation');

        return $accommodation instanceof \Artwork\Modules\Accommodation\Models\Accommodation
            && ($this->user()?->can('update', $accommodation) ?? false);
    }

    public function rules(): array
    {
        return [
            'id' => ['required', Rule::exists('accommodations', 'id')],
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone_number' => 'nullable|string|max:20',
            'street' => 'nullable|string|max:255',
            'zip_code' => 'nullable|string|max:20',
            'location' => 'nullable|string|max:255',
            'note' => 'nullable|string|max:65535',
            'room_types' => 'required|array|min:1',
            'room_types.*' => 'exists:accommodation_room_types,id',
            'room_type_costs' => 'nullable|array',
            'room_type_costs.*' => 'nullable|numeric|min:0|max:50000',
        ];
    }
}
