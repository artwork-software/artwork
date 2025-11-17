<?php

namespace Artwork\Modules\Shift\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShiftRule extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'trigger_type',
        'individual_number_value',
        'warning_color',
        'is_active'
    ];

    protected $casts = [
        'individual_number_value' => 'float',
        'is_active' => 'boolean'
    ];

    public function contracts(): BelongsToMany
    {
        return $this->belongsToMany(
            UserContract::class,
            'shift_rule_contract_assignments',
            'shift_rule_id',
            'contract_id'
        )->withTimestamps();
    }

    public function usersToNotify(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'shift_rule_user_notifications',
            'shift_rule_id',
            'user_id'
        )->withTimestamps();
    }

    public function shiftRuleViolations(): HasMany
    {
        return $this->hasMany(ShiftRuleViolation::class);
    }

    public function isActiveForContract(UserContract $contract): bool
    {
        if (!$this->is_active) {
            return false;
        }

        return $this->contracts()->where('contract_id', $contract->id)->exists();
    }
}
