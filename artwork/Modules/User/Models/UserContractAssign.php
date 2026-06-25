<?php

namespace Artwork\Modules\User\Models;

use Database\Factories\UserContractAssignFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $user_contract_id
 * @property int $free_full_days_per_week
 * @property int $free_half_days_per_week
 * @property bool $special_day_rule_active
 * @property int $compensation_period
 * @property int $free_sundays_per_season
 * @property float $days_off_first_26_weeks
 * @property string $created_at
 * @property string $updated_at
 * @property User $user
 * @property UserContract $userContract
 */
class UserContractAssign extends Model
{
    use HasFactory;

    protected static function newFactory(): Factory
    {
        return UserContractAssignFactory::new();
    }

    protected $fillable = [
        'user_id',
        'user_contract_id',
        'free_full_days_per_week',
        'free_half_days_per_week',
        'special_day_rule_active',
        'compensation_period',
        'overtime_rule_active',
        'overtime_compensation_period',
        'free_sundays_per_season',
        'days_off_first_26_weeks',
        'work_time_pattern_id',
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
        'saturday',
        'sunday',
        'valid_from',
        'valid_until',
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


    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id',
            'id',
            'user_contract_assigns'
        );
    }

    public function userContract(): BelongsTo
    {
        return $this->belongsTo(
            UserContract::class,
            'user_contract_id',
            'id',
            'user_contract_assigns'
        );
    }
}
