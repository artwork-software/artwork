<?php

namespace Artwork\Modules\Shift\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShiftRuleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'individual_number_value' => 'required|numeric|min:0.1',
            'warning_color' => 'required|string',
            'contract_ids' => 'nullable|array',
            'contract_ids.*' => 'exists:user_contracts,id',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id',
        ];
    }
}
