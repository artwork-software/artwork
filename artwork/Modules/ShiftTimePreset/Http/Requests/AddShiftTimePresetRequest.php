<?php

namespace Artwork\Modules\ShiftTimePreset\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddShiftTimePresetRequest extends FormRequest
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
            'name' => 'string|required',
            'break_time' => 'integer|required',
            'start_time' => 'required',
            'end_time' => 'required',
        ];
    }
}
