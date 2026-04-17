<?php

namespace Artwork\Modules\Shift\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreShiftRuleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'trigger_type' => 'required|string',
            'individual_number_value' => 'required|numeric|min:0.1',
            'warning_color' => 'required|string',
            'default_compensation_days' => 'nullable|numeric|min:0.5',
            'default_compensation_deadline_days' => 'nullable|integer|min:1',
            'contract_ids' => 'nullable|array',
            'contract_ids.*' => 'exists:user_contracts,id',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id',
        ];
    }
}
