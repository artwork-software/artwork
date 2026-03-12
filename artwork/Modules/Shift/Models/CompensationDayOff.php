<?php

namespace Artwork\Modules\Shift\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class CompensationDayOff extends Model
{
    use LogsActivity;

    protected static array $recordEvents = ['created', 'updated'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('compensation_day_off')
            ->logOnly(['user_id', 'violation_id', 'value', 'deadline', 'granted_date', 'granted_by', 'granted_at', 'reason'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'user_id',
        'violation_id',
        'value',
        'deadline',
        'granted_date',
        'granted_by',
        'granted_at',
        'reason',
    ];

    protected $casts = [
        'value' => 'decimal:1',
        'deadline' => 'date',
        'granted_date' => 'date',
        'granted_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id', 'user');
    }

    public function violation(): BelongsTo
    {
        return $this->belongsTo(ShiftRuleViolation::class, 'violation_id', 'id', 'violation');
    }

    public function grantedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by', 'id', 'grantedByUser');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNull('granted_at');
    }

    public function scopeGranted(Builder $query): Builder
    {
        return $query->whereNotNull('granted_at');
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->whereNull('granted_at')
            ->where('deadline', '<', now());
    }

    public function scopeForDate(Builder $query, string $date): Builder
    {
        return $query->where('granted_date', $date);
    }

    public function isFullDay(): bool
    {
        return (float) $this->value >= 1.0;
    }

    public function isHalfDay(): bool
    {
        return (float) $this->value < 1.0;
    }

    public function isGranted(): bool
    {
        return $this->granted_at !== null;
    }
}
