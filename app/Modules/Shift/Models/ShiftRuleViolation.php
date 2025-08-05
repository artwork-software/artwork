<?php

namespace Artwork\Modules\Shift\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShiftRuleViolation extends Model
{
    protected $fillable = [
        'shift_rule_id',
        'shift_id',
        'user_id',
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
        return $this->belongsTo(User::class, 'resolved_by');
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

    public function ignore(int $userId = null): void
    {
        $this->update([
            'status' => 'ignored',
            'resolved_at' => now(),
            'resolved_by' => $userId
        ]);
    }

    public function getViolationMessage(): string
    {
        return $this->shiftRule->description ?? 'Rule violation detected';
    }

    public function getWarningColor(): string
    {
        return $this->shiftRule->warning_color ?? '#ff0000';
    }
}
