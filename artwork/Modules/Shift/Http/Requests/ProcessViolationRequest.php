<?php

namespace Artwork\Modules\Shift\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProcessViolationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'compensation_days' => 'required|numeric|min:0.5',
            'compensation_deadline' => 'required|date|after:today',
            'compensation_reason' => 'nullable|string',
        ];
    }
}
