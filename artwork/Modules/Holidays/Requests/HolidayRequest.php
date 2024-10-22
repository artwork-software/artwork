<?php

namespace Artwork\Modules\Holidays\Requests;


use Illuminate\Foundation\Http\FormRequest;

class HolidayRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'rota' => ['nullable', 'integer'],
            'subdivisions' => ['required', 'array'],
            'subdivisions.*' => ['integer', 'exists:subdivisions,id'],
            'country' => ['required', 'string', 'size:2'],
        ];
    }
}
