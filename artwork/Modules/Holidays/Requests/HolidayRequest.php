<?php

namespace Artwork\Modules\Holidays\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HolidayRequest extends FormRequest
{
    /**
     * @return string[]
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'end_date' => ['nullable', 'date'],
            'rota' => ['nullable', 'integer'],
            'selectedSubdivisions' => ['nullable', 'array'],
            'selectedSubdivisions.*.id' => ['integer', 'exists:subdivisions,id'],
            'color' => ['nullable', 'string'],
            'yearly' => ['required', 'boolean'],
            'treatAsSpecialDay' => ['nullable', 'boolean'],
        ];
    }
}
