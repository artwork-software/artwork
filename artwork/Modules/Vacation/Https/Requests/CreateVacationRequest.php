<?php

namespace Artwork\Modules\Vacation\Https\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateVacationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'date' => 'required',
            'type' => 'required|string',
            'full_day' => 'nullable|boolean',
            'comment' => 'nullable|string|max:20',
            'is_serie' => 'nullable|boolean',
            'series_repeat' => 'nullable|string',
            'series_repeat_until' => 'nullable|date',
        ];
    }
}
