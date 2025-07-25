<?php

namespace Artwork\Modules\User\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserContractAssignRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => 'required|integer|exists:users,id',
            'user_contract_id' => 'nullable|integer|exists:user_contracts,id',
            'free_full_days_per_week' => 'nullable|integer|min:0',
            'free_half_days_per_week' => 'nullable|integer|min:0',
            'special_day_rule_active' => 'boolean',
            'compensation_period' => 'nullable|integer|min:0',
            'free_sundays_per_season' => 'nullable|integer|min:0',
            'days_off_first_26_weeks' => 'nullable|numeric|min:0'
        ];
    }
}
