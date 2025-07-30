<?php

namespace Artwork\Modules\User\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserFilterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'filter_type' => 'required|string|in:calendar_filter,shift_filter',
            'room_ids' => 'nullable|array',
            'area_ids' => 'nullable|array',
            'room_category_ids' => 'nullable|array',
            'room_attribute_ids' => 'nullable|array',
            'event_type_ids' => 'nullable|array',
            'event_property_ids' => 'nullable|array',
            'craft_ids' => 'nullable|array',
        ];
    }
}
