<?php

namespace Artwork\Modules\Shift\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreManualViolationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            // "Sonstiges (ohne Regel)": keine Regel, dafür ein Titel (max. 120 Zeichen)
            'shift_rule_id' => ['nullable', Rule::exists('shift_rules', 'id')->whereNull('deleted_at')],
            'title' => ['nullable', 'string', 'max:120', 'required_without:shift_rule_id'],
            'violation_date' => 'required|date',
            'reason' => 'nullable|string',
            'severity' => 'in:warning,error',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required_without' => __('Please select a rule or enter a title for the violation.'),
        ];
    }

    protected function prepareForValidation(): void
    {
        // Das Formular sendet 0 / '' für "Sonstiges (ohne Regel)"
        if (in_array($this->input('shift_rule_id'), [0, '0', ''], true)) {
            $this->merge(['shift_rule_id' => null]);
        }
    }
}
