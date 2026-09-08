<?php

namespace Artwork\Modules\AppApi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AppShiftRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'day' => ['required', 'date_format:Y-m-d'],
            'start' => ['required', 'date_format:H:i'],
            'end' => ['required', 'date_format:H:i'],
            'break_minutes' => ['required', 'integer', 'min:0', 'max:1440'],
            'description' => ['nullable', 'string', 'max:1000'],
            'craft_id' => ['required', 'integer', 'exists:crafts,id'],
            'room_id' => ['nullable', 'integer', 'exists:rooms,id'],
            // The shift's staffing requirements; values below the people
            // already booked are clamped up to the booked count (core rule).
            'qualifications' => ['present', 'array'],
            'qualifications.*.shift_qualification_id' => [
                'required',
                'integer',
                'exists:shift_qualifications,id',
            ],
            'qualifications.*.value' => ['required', 'integer', 'min:0', 'max:99'],
        ];
    }
}
