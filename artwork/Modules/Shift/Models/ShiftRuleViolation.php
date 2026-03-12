<?php

namespace Artwork\Modules\Shift\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Workflow\Contracts\WorkflowSubject;
use Artwork\Modules\Workflow\Traits\HasWorkflows;
use Artwork\Modules\Workflow\Traits\TriggersWorkflows;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShiftRuleViolation extends Model implements WorkflowSubject
{
    use HasFactory, HasWorkflows, TriggersWorkflows;

    protected $fillable = [
        'shift_rule_id',
        'shift_id',
        'user_id',
        'violation_date',
        'violation_data',
        'severity',
        'status',
        'resolved_at',
        'resolved_by',
        'reason',
        'is_manual',
        'created_by_user_id',
        'compensation_days',
        'compensation_deadline',
        'compensation_reason',
        'compensation_granted_at',
        'compensation_granted_by',
        'parent_violation_id',
    ];

    protected $casts = [
        'violation_date' => 'date',
        'violation_data' => 'array',
        'resolved_at' => 'datetime',
        'is_manual' => 'boolean',
        'compensation_days' => 'decimal:1',
        'compensation_deadline' => 'date',
        'compensation_granted_at' => 'datetime',
    ];

    public function shiftRule(): BelongsTo
    {
        return $this->belongsTo(ShiftRule::class, 'shift_rule_id', 'id', 'shiftRule');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'shift_id', 'id', 'shift');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id', 'user');
    }

    public function resolvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by', 'id', 'resolvedByUser');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id', 'id', 'createdByUser');
    }

    public function grantedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'compensation_granted_by', 'id', 'grantedByUser');
    }

    public function parentViolation(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_violation_id', 'id', 'parentViolation');
    }

    public function childViolations(): HasMany
    {
        return $this->hasMany(self::class, 'parent_violation_id');
    }

    public function scopeOpenCompensation(Builder $query): Builder
    {
        return $query->where('status', 'active')
            ->whereNotNull('compensation_days')
            ->whereNull('compensation_granted_at');
    }

    public function scopeGrantedCompensation(Builder $query): Builder
    {
        return $query->whereNotNull('compensation_granted_at');
    }

    public function scopeOverdueCompensation(Builder $query): Builder
    {
        return $query->where('status', 'active')
            ->whereNotNull('compensation_deadline')
            ->whereNull('compensation_granted_at')
            ->where('compensation_deadline', '<', now());
    }

    public function isResolved(): bool
    {
        return $this->status === 'resolved' && $this->resolved_at !== null;
    }

    public function resolve(?int $userId = null): void
    {
        $this->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolved_by' => $userId,
        ]);
    }

    public function ignore(?int $userId = null): void
    {
        $this->update([
            'status' => 'ignored',
            'resolved_at' => now(),
            'resolved_by' => $userId,
        ]);
    }

    public function grantCompensation(int $userId): void
    {
        $this->update([
            'compensation_granted_at' => now(),
            'compensation_granted_by' => $userId,
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolved_by' => $userId,
        ]);
    }

    public function hasCompensation(): bool
    {
        return $this->compensation_days !== null && $this->compensation_days > 0;
    }

    public function isCompensationOverdue(): bool
    {
        return $this->compensation_deadline !== null
            && $this->compensation_granted_at === null
            && $this->compensation_deadline->isPast();
    }

    public function getViolationMessage(): string
    {
        return $this->shiftRule?->description ?? 'Rule violation detected';
    }

    public function getWarningColor(): string
    {
        $color = $this->shiftRule?->warning_color ?? '#ff0000';
        return empty($color) ? '#ff0000' : $color;
    }

    public function canHaveWorkflow(string $workflowType): bool
    {
        return in_array($workflowType, ['shift_violation_approval', 'shift_violation_management']);
    }
}
