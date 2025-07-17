<?php

namespace Artwork\Modules\User\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    protected $fillable = [
        'name',
        'free_full_days_per_week',
        'free_half_days_per_week',
        'special_day_rule_active',
        'compensation_period',
        'description',
        'free_sundays_per_season',
        'days_off_first_26_weeks'
    ];

    protected $casts = [
        'special_day_rule_active' => 'boolean',
        'days_off_first_26_weeks' => 'float',
        'free_full_days_per_week' => 'integer',
        'free_half_days_per_week' => 'integer',
        'compensation_period' => 'integer',
        'free_sundays_per_season' => 'integer'
    ];
}
