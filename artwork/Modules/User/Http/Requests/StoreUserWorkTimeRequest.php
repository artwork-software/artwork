<?php

namespace Artwork\Modules\User\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserWorkTimeRequest extends FormRequest
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
            'work_time_pattern_id' => 'nullable|integer|exists:user_work_time_patterns,id',
            'monday' => 'nullable|date_format:H:i',
            'tuesday' => 'nullable|date_format:H:i',
            'wednesday' => 'nullable|date_format:H:i',
            'thursday' => 'nullable|date_format:H:i',
            'friday' => 'nullable|date_format:H:i',
            'saturday' => 'nullable|date_format:H:i',
            'sunday' => 'nullable|date_format:H:i',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after_or_equal:valid_from'
        ];
    }
}
