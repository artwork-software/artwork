<?php

namespace Artwork\Modules\Workflow\Models;

use Artwork\Core\Database\Models\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @deprecated This model is deprecated and replaced by Artwork\Modules\Shift\Models\ShiftRuleViolation
 * The new system links violations directly to shifts instead of using polymorphic relationships
 * @see \Artwork\Modules\Shift\Models\ShiftRuleViolation
 */
class WorkflowRuleViolation extends Model
{
    protected $fillable = [
        'workflow_rule_id',
        'subject_type',
        'subject_id',
        'violation_date',
        'violation_data',
        'severity',
        'status',
        'resolved_at',
        'resolved_by'
    ];

    protected $casts = [
        'violation_date' => 'date',
        'violation_data' => 'array',
        'resolved_at' => 'datetime'
    ];

    public function workflowRule(): BelongsTo
    {
        return $this->belongsTo(WorkflowRule::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function isResolved(): bool
    {
        return $this->status === 'resolved' && $this->resolved_at !== null;
    }

    public function resolve(int $userId = null): void
    {
        $this->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolved_by' => $userId
        ]);
    }

    public function getViolationMessage(): string
    {
        return $this->workflowRule->description ?? 'Rule violation detected';
    }

    public function getWarningColor(): string
    {
        return $this->workflowRule->warning_color ?? '#ff0000';
    }
}
