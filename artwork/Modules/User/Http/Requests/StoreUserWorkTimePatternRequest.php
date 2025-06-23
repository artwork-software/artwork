<?php

namespace Artwork\Modules\User\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserWorkTimePatternRequest extends FormRequest
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
            'description' => 'nullable|string|max:500',
            'monday' => 'nullable|date_format:H:i',
            'tuesday' => 'nullable|date_format:H:i',
            'wednesday' => 'nullable|date_format:H:i',
            'thursday' => 'nullable|date_format:H:i',
            'friday' => 'nullable|date_format:H:i',
            'saturday' => 'nullable|date_format:H:i',
            'sunday' => 'nullable|date_format:H:i',
        ];
    }
}
