<?php

namespace Artwork\Modules\Shift\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TimelinePresetSearchRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => 'required|string|min:1'
        ];
    }
}
