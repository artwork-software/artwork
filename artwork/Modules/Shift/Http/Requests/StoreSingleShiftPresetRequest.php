<?php

namespace Artwork\Modules\Shift\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSingleShiftPresetRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'start_time' => ['required'],
            'end_time' => ['required'],
            'break_duration' => ['nullable', 'integer', 'min:0'],
            'craft_id' => ['required', 'exists:crafts,id'],
            'description' => ['nullable', 'string', 'max:1000'],
            'shift_qualifications' => ['array'],
            'shift_qualifications.*.id' => ['required', 'exists:shift_qualifications,id'],
            'shift_qualifications.*.quantity' => ['required', 'integer', 'min:0'],
        ];
    }
}
