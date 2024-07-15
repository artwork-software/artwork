<?php

namespace Artwork\Modules\Vacation\Https\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateVacationRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'start_time' => 'nullable',
            'end_time' => 'nullable',
            'date' => 'required',
            'type' => 'required|string',
            'full_day' => 'nullable|boolean',
            'comment' => 'nullable|string|max:20',
            'is_series' => 'nullable|boolean',
            'series_repeat' => 'nullable|string',
            'series_repeat_until' => 'nullable|date',
        ];
    }
}
