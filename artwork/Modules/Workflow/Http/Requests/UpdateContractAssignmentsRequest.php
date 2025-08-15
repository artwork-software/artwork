<?php

namespace Artwork\Modules\Workflow\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateContractAssignmentsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'rule_ids' => 'array',
            'rule_ids.*' => 'exists:workflow_rules,id'
        ];
    }
}
