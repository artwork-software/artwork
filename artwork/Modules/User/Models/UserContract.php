<?php

namespace Artwork\Modules\User\Models;

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
