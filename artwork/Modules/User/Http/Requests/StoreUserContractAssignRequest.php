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
            'id' => 'nullable|integer|exists:user_work_times,id',
            'user_contract_id' => 'nullable|integer|exists:user_contracts,id',
            'free_full_days_per_week' => 'nullable|integer|min:0',
            'free_half_days_per_week' => 'nullable|integer|min:0',
            'special_day_rule_active' => 'boolean',
            'compensation_period' => 'nullable|integer|min:0',
            'overtime_rule_active' => 'boolean',
            'overtime_compensation_period' => 'nullable|integer|min:1',
            'free_sundays_per_season' => 'nullable|integer|min:0',
            'days_off_first_26_weeks' => 'nullable|numeric|min:0',
            // Spielzeitbezogene Infodaten (DP-18)
            'free_sundays_per_season_active' => 'boolean',
            'days_off_first_26_weeks_active' => 'boolean',
            'free_sundays_sat_mon_per_half' => 'nullable|integer|min:0',
            'free_sundays_sat_mon_per_half_active' => 'boolean',
            'free_sundays_and_saturdays_per_season' => 'nullable|integer|min:0',
            'free_sundays_and_saturdays_per_season_active' => 'boolean',
            'free_sundays_per_calendar_year' => 'nullable|integer|min:0',
            'free_sundays_per_calendar_year_active' => 'boolean',
            'one_and_half_day_combinations' => 'nullable|integer|min:0',
            'one_and_half_day_combinations_active' => 'boolean',
            'annual_vacation_days' => 'nullable|integer|min:0',
            'work_time_pattern_id' => 'nullable|integer|exists:user_work_time_patterns,id',
            'monday' => 'nullable|string',
            'tuesday' => 'nullable|string',
            'wednesday' => 'nullable|string',
            'thursday' => 'nullable|string',
            'friday' => 'nullable|string',
            'saturday' => 'nullable|string',
            'sunday' => 'nullable|string',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after_or_equal:valid_from'
        ];
    }
}
