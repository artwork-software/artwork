<?php

namespace Artwork\Modules\Shift\Models;

use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Persistierter Spielzeit-Snapshot der getrackten Kennzahlen je User (DP-18).
 *
 * @property int $id
 * @property int $user_id
 * @property string $season_start
 * @property string $season_end
 * @property int $calendar_year
 * @property int $free_sundays_sat_mon_half1
 * @property int $free_sundays_sat_mon_half2
 * @property int $free_sundays_and_saturdays_season
 * @property int $free_sundays_calendar_year
 * @property int $free_sundays_per_season
 * @property int $one_and_half_combos_half1
 * @property int $one_and_half_combos_half2
 * @property int $granted_half_free_days_half1
 * @property int $granted_half_free_days_half2
 * @property float $days_off_first_26_weeks_count
 * @property float $granted_vacation_days_year
 * @property string|null $recalculated_at
 */
class UserShiftKpiSnapshot extends Model
{
    protected $fillable = [
        'user_id',
        'season_start',
        'season_end',
        'calendar_year',
        'free_sundays_sat_mon_half1',
        'free_sundays_sat_mon_half2',
        'free_sundays_and_saturdays_season',
        'free_sundays_calendar_year',
        'free_sundays_per_season',
        'one_and_half_combos_half1',
        'one_and_half_combos_half2',
        'granted_half_free_days_half1',
        'granted_half_free_days_half2',
        'days_off_first_26_weeks_count',
        'granted_vacation_days_year',
        'recalculated_at',
    ];

    protected $casts = [
        'season_start' => 'date',
        'season_end' => 'date',
        'calendar_year' => 'integer',
        'free_sundays_sat_mon_half1' => 'integer',
        'free_sundays_sat_mon_half2' => 'integer',
        'free_sundays_and_saturdays_season' => 'integer',
        'free_sundays_calendar_year' => 'integer',
        'free_sundays_per_season' => 'integer',
        'one_and_half_combos_half1' => 'integer',
        'one_and_half_combos_half2' => 'integer',
        'granted_half_free_days_half1' => 'integer',
        'granted_half_free_days_half2' => 'integer',
        'days_off_first_26_weeks_count' => 'float',
        'granted_vacation_days_year' => 'float',
        'recalculated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
