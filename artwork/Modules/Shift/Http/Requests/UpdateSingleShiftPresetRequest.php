<?php

namespace Artwork\Modules\Shift\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSingleShiftPresetRequest extends FormRequest
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
            'name' => ['sometimes', 'string', 'max:255'],
            'start_time' => ['sometimes'],
            'end_time' => ['sometimes'],
            'break_duration' => ['nullable', 'integer', 'min:0'],
            'craft_id' => ['sometimes', 'exists:crafts,id'],
            'description' => ['nullable', 'string', 'max:1000'],
            'shift_qualifications' => ['array'],
            'shift_qualifications.*.id' => ['required_with:shift_qualifications', 'exists:shift_qualifications,id'],
            'shift_qualifications.*.quantity' => ['required_with:shift_qualifications', 'integer', 'min:0'],
        ];
    }
}
