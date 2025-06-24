<?php

namespace Artwork\Modules\User\Models;

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

    protected $fillable = [
        'user_id',
        'user_contract_id',
        'free_full_days_per_week',
        'free_half_days_per_week',
        'special_day_rule_active',
        'compensation_period',
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
