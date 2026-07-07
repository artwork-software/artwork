<?php

namespace Artwork\Modules\Shift\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignContractsToRuleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'contract_ids' => 'required|array',
            'contract_ids.*' => 'exists:user_contracts,id',
        ];
    }
}
