<?php

namespace Artwork\Modules\User\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserFilterTemplateRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'filter_type' => 'required|string|in:calendar_filter,shift_filter,planning_filter,project_shift_filter',
            'event_type_ids' => 'nullable|array',
            'event_type_ids.*' => 'integer|exists:event_types,id',
            'room_ids' => 'nullable|array',
            'room_ids.*' => 'integer|exists:rooms,id',
            'area_ids' => 'nullable|array',
            'area_ids.*' => 'integer|exists:areas,id',
            'room_attribute_ids' => 'nullable|array',
            'room_attribute_ids.*' => 'integer|exists:room_attributes,id',
            'room_category_ids' => 'nullable|array',
            'room_category_ids.*' => 'integer|exists:room_categories,id',
            'event_property_ids' => 'nullable|array',
            'event_property_ids.*' => 'integer|exists:event_properties,id',
            'craft_ids' => 'nullable|array',
            'craft_ids.*' => 'integer|exists:crafts,id',
        ];
    }
}
