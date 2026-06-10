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
            // Überstunden-Regel (Vorgabe für die Zuweisung am User)
            'overtime_rule_active' => 'boolean',
            'overtime_compensation_period' => 'nullable|integer|min:1',
        ];
    }
}
