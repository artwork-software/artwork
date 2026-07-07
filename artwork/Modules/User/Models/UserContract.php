<?php

namespace Artwork\Modules\User\Models;

use Database\Factories\UserContractFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 *
 * @property int $id
 * @property string $name
 * @property int $free_full_days_per_week
 * @property int $free_half_days_per_week
 * @property bool $special_day_rule_active
 * @property int $compensation_period
 * @property string|null $description
 * @property int $free_sundays_per_season
 * @property float $days_off_first_26_weeks
 * @property string $created_at
 * @property string $updated_at
 */
class UserContract extends Model
{
    use HasFactory;

    protected static function newFactory(): Factory
    {
        return UserContractFactory::new();
    }

    protected $fillable = [
        'name',
        'free_full_days_per_week',
        'free_half_days_per_week',
        'special_day_rule_active',
        'compensation_period',
        'overtime_rule_active',
        'overtime_compensation_period',
        'description',
        'free_sundays_per_season',
        'days_off_first_26_weeks',
        // Spielzeitbezogene Infodaten (DP-18)
        'free_sundays_per_season_active',
        'days_off_first_26_weeks_active',
        'free_sundays_sat_mon_per_half',
        'free_sundays_sat_mon_per_half_active',
        'free_sundays_and_saturdays_per_season',
        'free_sundays_and_saturdays_per_season_active',
        'free_sundays_per_calendar_year',
        'free_sundays_per_calendar_year_active',
        'one_and_half_day_combinations',
        'one_and_half_day_combinations_active',
        'annual_vacation_days',
    ];

    protected $casts = [
        'special_day_rule_active' => 'boolean',
        'overtime_rule_active' => 'boolean',
        'overtime_compensation_period' => 'integer',
        'days_off_first_26_weeks' => 'float',
        'free_full_days_per_week' => 'integer',
        'free_half_days_per_week' => 'integer',
        'compensation_period' => 'integer',
        'free_sundays_per_season' => 'integer',
        // Spielzeitbezogene Infodaten (DP-18)
        'free_sundays_per_season_active' => 'boolean',
        'days_off_first_26_weeks_active' => 'boolean',
        'free_sundays_sat_mon_per_half' => 'integer',
        'free_sundays_sat_mon_per_half_active' => 'boolean',
        'free_sundays_and_saturdays_per_season' => 'integer',
        'free_sundays_and_saturdays_per_season_active' => 'boolean',
        'free_sundays_per_calendar_year' => 'integer',
        'free_sundays_per_calendar_year_active' => 'boolean',
        'one_and_half_day_combinations' => 'integer',
        'one_and_half_day_combinations_active' => 'boolean',
        'annual_vacation_days' => 'integer',
    ];

    /**
     * @deprecated Use shiftRules() instead
     */
    public function workflowRules(): BelongsToMany
    {
        return $this->belongsToMany(
            \Artwork\Modules\Workflow\Models\WorkflowRule::class,
            'workflow_rule_contract_assignments',
            'contract_id',
            'workflow_rule_id'
        )->withTimestamps();
    }

    public function userContractAssigns(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(UserContractAssign::class, 'user_contract_id');
    }

    public function shiftRules(): BelongsToMany
    {
        return $this->belongsToMany(
            \Artwork\Modules\Shift\Models\ShiftRule::class,
            'shift_rule_contract_assignments',
            'contract_id',
            'shift_rule_id'
        )->withTimestamps();
    }
}
