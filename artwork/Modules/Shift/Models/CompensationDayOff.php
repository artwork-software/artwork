<?php

namespace Artwork\Modules\Shift\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Shift\Models\Traits\LogsShiftPlanDayActivity;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class CompensationDayOff extends Model
{
    use LogsActivity;
    use LogsShiftPlanDayActivity;

    protected static array $recordEvents = ['created', 'updated'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('compensation_day_off')
            ->logOnly([
                'user_id',
                'violation_id',
                'value',
                'half_day_period',
                'deadline',
                'granted_date',
                'granted_by',
                'granted_at',
                'reason',
                'for_holiday',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected function shiftHistoryDayEntryKind(): string
    {
        return 'compensation';
    }

    /**
     * @return array{0: ?string, 1: ?int}
     */
    protected function shiftHistoryPerson(): array
    {
        return [User::class, $this->user_id !== null ? (int) $this->user_id : null];
    }

    /**
     * Tag im Schichtverlauf: der genommene Ausgleichstag (granted_date). Beim Zurücknehmen der Tag, der
     * vorher eingetragen war; ein reines Guthaben ohne Tag erscheint nicht im Tagesverlauf.
     *
     * @return array{0: ?string, 1: ?string}
     */
    protected function shiftHistoryDayRange(): array
    {
        $date = $this->getAttributes()['granted_date'] ?? $this->getRawOriginal('granted_date');
        $day = $date ? Carbon::parse($date)->toDateString() : null;

        return [$day, $day];
    }

    /**
     * @return array<string, mixed>
     */
    protected function shiftHistoryDayEntryData(): array
    {
        return [
            'value' => $this->value !== null ? (float) $this->value : null,
            'half_day_period' => $this->half_day_period,
            'granted' => !empty($this->getAttributes()['granted_date']),
            'for_holiday' => (bool) $this->for_holiday,
        ];
    }

    protected $fillable = [
        'user_id',
        'violation_id',
        'value',
        'half_day_period',
        'deadline',
        'granted_date',
        'granted_by',
        'granted_at',
        'reason',
        'for_holiday',
    ];

    protected $casts = [
        'value' => 'decimal:1',
        'deadline' => 'date',
        'granted_date' => 'date',
        'granted_at' => 'datetime',
        'for_holiday' => 'boolean',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id', 'user');
    }

    /**
     * @return BelongsTo<ShiftRuleViolation, $this>
     */
    public function violation(): BelongsTo
    {
        return $this->belongsTo(ShiftRuleViolation::class, 'violation_id', 'id', 'violation');
    }

    /**
     * @return BelongsTo<User, $this>
     */
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

    public function isMorning(): bool
    {
        return $this->half_day_period === 'morning';
    }

    public function isAfternoon(): bool
    {
        return $this->half_day_period === 'afternoon';
    }
}
