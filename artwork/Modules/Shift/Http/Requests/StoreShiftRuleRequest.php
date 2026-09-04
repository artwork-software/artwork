<?php

namespace Artwork\Modules\Shift\Http\Requests;

use Artwork\Modules\Shift\Services\ShiftRuleService;
use Illuminate\Foundation\Http\FormRequest;

class StoreShiftRuleRequest extends FormRequest
{
    public function rules(): array
    {
        $typesWithoutValue = implode(',', ShiftRuleService::ruleTypesWithoutValue());

        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'trigger_type' => 'required|string',
            // Zahlenwert nur für Regeltypen, die ihn brauchen; für Sonntag/Sondertag/HFT an Sondertag ignoriert.
            'individual_number_value' => [
                'nullable',
                'required_unless:trigger_type,' . $typesWithoutValue,
                'numeric',
                'min:0.1',
            ],
            'warning_color' => 'required|string',
            'default_compensation_days' => 'nullable|numeric|min:0.5',
            'default_compensation_deadline_days' => 'nullable|integer|min:1',
            'contract_ids' => 'nullable|array',
            'contract_ids.*' => 'exists:user_contracts,id',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Für Typen ohne Wert kommt vom Formular ggf. 0/''/null — nicht an min:0.1 scheitern lassen.
        if (in_array($this->input('trigger_type'), ShiftRuleService::ruleTypesWithoutValue(), true)) {
            $this->merge(['individual_number_value' => null]);
        }
    }
}
