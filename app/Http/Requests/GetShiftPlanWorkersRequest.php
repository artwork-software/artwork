<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetShiftPlanWorkersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'craft_ids' => ['nullable', 'array'],
            'craft_ids.*' => ['integer', 'exists:crafts,id'],
        ];
    }
}
