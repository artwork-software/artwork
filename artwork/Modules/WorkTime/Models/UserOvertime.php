<?php

namespace Artwork\Modules\WorkTime\Models;

use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property int $user_id
 * @property \Illuminate\Support\Carbon $date
 * @property int $minutes
 * @property int $remaining_minutes
 * @property int $paid_out_minutes
 * @property \Illuminate\Support\Carbon $deadline
 * @property string $status
 * @property int|null $paid_out_by
 * @property \Illuminate\Support\Carbon|null $paid_out_at
 * @property string|null $payout_reason
 */
class UserOvertime extends Model
{
    use LogsActivity;

    protected static array $recordEvents = ['created', 'updated'];

    public const STATUS_OPEN = 'open';
    public const STATUS_COMPENSATED = 'compensated';
    public const STATUS_PAYABLE = 'payable';
    public const STATUS_PAID_OUT = 'paid_out';

    protected $fillable = [
        'user_id',
        'date',
        'minutes',
        'remaining_minutes',
        'paid_out_minutes',
        'deadline',
        'status',
        'paid_out_by',
        'paid_out_at',
        'payout_reason',
    ];

    protected $casts = [
        'date' => 'date',
        'deadline' => 'date',
        'paid_out_at' => 'datetime',
        'minutes' => 'integer',
        'remaining_minutes' => 'integer',
        'paid_out_minutes' => 'integer',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('user_overtime')
            ->logOnly(['user_id', 'date', 'minutes', 'remaining_minutes', 'paid_out_minutes', 'deadline', 'status', 'paid_out_by', 'paid_out_at', 'payout_reason'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id', 'user');
    }

    public function paidOutByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_out_by', 'id', 'paidOutByUser');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    public function scopePayable(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PAYABLE);
    }

    public function scopePaidOut(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PAID_OUT);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function isPayable(): bool
    {
        return $this->status === self::STATUS_PAYABLE;
    }

    public function isPaidOut(): bool
    {
        return $this->status === self::STATUS_PAID_OUT;
    }
}
