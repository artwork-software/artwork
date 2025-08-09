<?php

namespace Artwork\Modules\Workflow\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreShiftWarningRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'trigger_type' => 'required|string',
            'individual_number_value' => 'required|numeric|min:0',
            'warning_color' => 'required|string|regex:/^#[a-fA-F0-9]{6}$/',
            'notify_on_violation' => 'boolean',
            'contract_ids' => 'array',
            'contract_ids.*' => 'exists:user_contracts,id',
            'user_ids' => 'array',
            'user_ids.*' => 'exists:users,id'
        ];
    }
}
