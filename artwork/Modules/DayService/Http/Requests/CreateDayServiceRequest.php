<?php

namespace Artwork\Modules\DayService\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateDayServiceRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required',
            'icon' => 'required',
            'hex_color' => 'required',
        ];
    }
}
