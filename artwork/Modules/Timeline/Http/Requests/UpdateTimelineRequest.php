<?php

namespace Artwork\Modules\Timeline\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTimelineRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'start_date' => 'required|string',
            'end_date' => 'required|string',
            'start' => 'required|string',
            'end' => 'required|string',
            'description' => 'string|nullable',
        ];
    }
}
