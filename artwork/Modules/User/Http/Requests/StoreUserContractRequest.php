<?php

namespace Artwork\Modules\User\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserContractRequest extends FormRequest
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
     *     'name',
     * 'free_full_days_per_week',
     * 'free_half_days_per_week',
     * 'special_day_rule_active',
     * 'compensation_period',
     * 'description',
     * 'free_sundays_per_season',
     * 'days_off_first_26_weeks'
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'free_full_days_per_week' => 'required|integer|min:0',
            'free_half_days_per_week' => 'required|integer|min:0',
            'special_day_rule_active' => 'boolean',
            'compensation_period' => 'required|integer|min:0',
            'description' => 'nullable|string|max:1000',
            'free_sundays_per_season' => 'required|integer|min:0',
            'days_off_first_26_weeks' => 'required|numeric|min:0',
        ];
    }
}
