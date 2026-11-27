<?php

namespace Artwork\Modules\Shift\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Shift\Enums\ShortNoticeReason;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Services\ShortNoticeChangeMarker;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommittedShiftChange extends Model
{
    protected $fillable = [
        'craft_id',
        'shift_id',
        'subject_type',
        'subject_id',
        'change_type',
        'field_changes',
        'affected_user_type',
        'affected_user_id',
        'changed_by_user_id',
        'changed_at',
        'is_short_notice',
        'short_notice_reason',
        'short_notice_note',
        'acknowledged_at',
        'acknowledged_by_user_id',
    ];

    protected $casts = [
        'field_changes'    => 'array',
        'changed_at'       => 'datetime',
        'acknowledged_at'  => 'datetime',
        'is_short_notice'  => 'boolean',
        'short_notice_reason' => ShortNoticeReason::class,
    ];

    /**
     * Kurzfrist-Markierung beim Entstehen: gilt für alle Stellen, die Einträge anlegen
     * (ShiftChangeRecorder, ShiftService, ShiftWorkerService). Bestandseinträge bleiben unmarkiert,
     * ein ausdrücklich gesetzter Wert wird nicht überschrieben.
     */
    protected static function booted(): void
    {
        static::creating(static function (CommittedShiftChange $change): void {
            if ($change->isDirty('is_short_notice')) {
                return;
            }

            $change->is_short_notice = app(ShortNoticeChangeMarker::class)->isShortNotice($change);
        });
    }

    /**
     * @return BelongsTo<Craft, $this>
     */
    public function craft(): BelongsTo
    {
        return $this->belongsTo(Craft::class, 'craft_id', 'id', 'crafts');
    }

    /**
     * @return BelongsTo<Shift, $this>
     */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'shift_id', 'id', 'shifts');
    }

    public function affectedUser(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        // Polymorphe Relation: affected_user kann User, Freelancer oder ServiceProvider sein
        return $this->morphTo(null, 'affected_user_type', 'affected_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function changedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id', 'id', 'users');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function acknowledgedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by_user_id', 'id', 'users');
    }

    public function subject(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo(null, 'subject_type', 'subject_id');
    }
}
