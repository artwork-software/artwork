<?php

namespace Artwork\Modules\Shift\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TimelinePresetStoreRequest extends FormRequest
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
            'dataset' => 'required|array',
        ];
    }

    /**
     * Customize the validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => __('validation.timeline.name_required'),
            'name.string' => __('validation.timeline.name_string'),
            'name.max' => __('validation.timeline.name_max'),
            'dataset.required' => __('validation.timeline.dataset_required'),
            'dataset.array' => __('validation.timeline.dataset_array'),
            'dataset.*.start.required' => __('validation.timeline.start_required'),
            'dataset.*.start.date_format' => __('validation.timeline.start_date_format'),
            'dataset.*.end.required' => __('validation.timeline.end_required'),
            'dataset.*.end.date_format' => __('validation.timeline.end_date_format'),
            'dataset.*.end.after' => __('validation.timeline.end_after_start'),
            'dataset.*.description.string' => __('validation.timeline.description_string'),
            'dataset.*.description.max' => __('validation.timeline.description_max'),
        ];
    }
}
