<?php

namespace Artwork\Modules\DayService\Http\Requests;

use Artwork\Core\Validation\Rules\IconNameRule;
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
            'icon' => ['required', 'string', new IconNameRule()],
            'hex_color' => 'required',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => __('Name'),
            'icon' => __('Icon'),
            'hex_color' => __('Color'),
        ];
    }
}
