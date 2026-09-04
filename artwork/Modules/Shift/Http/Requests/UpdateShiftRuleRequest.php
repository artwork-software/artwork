<?php

namespace Artwork\Modules\Shift\Http\Requests;

use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\Shift\Services\ShiftRuleService;
use Illuminate\Foundation\Http\FormRequest;

class UpdateShiftRuleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            // Der Regeltyp ist beim Bearbeiten fix (Route-Model); Zahlenwert nur, wenn der Typ ihn braucht.
            'individual_number_value' => $this->ruleTypeNeedsValue()
                ? 'required|numeric|min:0.1'
                : 'nullable|numeric',
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
        if (!$this->ruleTypeNeedsValue()) {
            $this->merge(['individual_number_value' => null]);
        }
    }

    private function ruleTypeNeedsValue(): bool
    {
        $rule = $this->route('shiftRule');
        $triggerType = $rule instanceof ShiftRule ? $rule->trigger_type : null;

        return !in_array($triggerType, ShiftRuleService::ruleTypesWithoutValue(), true);
    }
}
