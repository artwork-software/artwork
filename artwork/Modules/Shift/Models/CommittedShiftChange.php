<?php

namespace Artwork\Modules\Shift\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\User\Models\User;

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
        'acknowledged_at',
        'acknowledged_by_user_id',
    ];

    protected $casts = [
        'field_changes'    => 'array',
        'changed_at'       => 'datetime',
        'acknowledged_at'  => 'datetime',
    ];

    public function craft()
    {
        return $this->belongsTo(Craft::class, 'craft_id', 'id', 'crafts');
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class, 'shift_id', 'id', 'shifts');
    }

    public function affectedUser(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        // Polymorphe Relation: affected_user kann User, Freelancer oder ServiceProvider sein
        return $this->morphTo(null, 'affected_user_type', 'affected_user_id');
    }

    public function changedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id', 'id', 'users');
    }

    public function acknowledgedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by_user_id', 'id', 'users');
    }

    public function subject(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo(null, 'subject_type', 'subject_id');
    }
}
