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
            'shift_rule_id' => ['required', Rule::exists('shift_rules', 'id')->whereNull('deleted_at')],
            'violation_date' => 'required|date',
            'reason' => 'nullable|string',
            'severity' => 'in:warning,error',
        ];
    }
}
