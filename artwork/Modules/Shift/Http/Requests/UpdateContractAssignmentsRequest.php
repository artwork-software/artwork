<?php

namespace Artwork\Modules\Shift\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateContractAssignmentsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'rule_ids' => 'nullable|array',
            'rule_ids.*' => 'exists:shift_rules,id',
        ];
    }
}
