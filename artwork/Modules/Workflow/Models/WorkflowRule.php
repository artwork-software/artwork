<?php

namespace Artwork\Modules\Workflow\Models;

use Artwork\Core\Database\Models\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkflowRule extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'trigger_type',
        'individual_number_value',
        'warning_color',
        'is_active',
        'configuration',
        'notify_on_violation'
    ];

    protected $casts = [
        'individual_number_value' => 'float',
        'is_active' => 'boolean',
        'notify_on_violation' => 'boolean',
        'configuration' => 'array'
    ];

    public function workflowRuleViolations(): HasMany
    {
        return $this->hasMany(WorkflowRuleViolation::class);
    }

    public function workflowRuleAssignments(): HasMany
    {
        return $this->hasMany(WorkflowRuleAssignment::class);
    }

    public function isActiveForSubject($subject): bool
    {
        if (!$this->is_active) {
            return false;
        }

        return $this->workflowRuleAssignments()
            ->where('subject_type', get_class($subject))
            ->where('subject_id', $subject->id)
            ->exists();
    }

    public function getValidationConfig(): array
    {
        return array_merge([
            'trigger_type' => $this->trigger_type,
            'value' => $this->individual_number_value,
            'warning_color' => $this->warning_color
        ], $this->configuration ?? []);
    }

    public function usersToNotify(): BelongsToMany
    {
        return $this->belongsToMany(
            \Artwork\Modules\User\Models\User::class,
            'workflow_rule_user_notifications',
            'workflow_rule_id',
            'user_id'
        )->withTimestamps();
    }

    public function contracts(): BelongsToMany
    {
        return $this->belongsToMany(
            \Artwork\Modules\Contract\Models\Contract::class,
            'workflow_rule_contract_assignments',
            'workflow_rule_id',
            'contract_id'
        )->withTimestamps();
    }

    public function shouldNotifyOnViolation(): bool
    {
        return $this->notify_on_violation === true;
    }
}
