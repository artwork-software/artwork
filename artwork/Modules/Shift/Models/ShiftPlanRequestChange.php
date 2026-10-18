<?php

namespace Artwork\Modules\Shift\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Shift\Models\ShiftPlanRequest;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShiftPlanRequestChange extends Model
{
    protected $fillable = [
        'shift_plan_request_id',
        'subject_type',
        'subject_id',
        'change_type',
        'field_changes',
        'affected_user_id',
        'changed_by_user_id',
        'changed_at',
    ];

    protected $casts = [
        'field_changes' => 'array',
        'changed_at'    => 'datetime',
    ];

    /**
     * @return BelongsTo<ShiftPlanRequest, $this>
     */
    public function request(): BelongsTo
    {
        return $this->belongsTo(ShiftPlanRequest::class, 'shift_plan_request_id', 'id', 'shift_plan_requests');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function affectedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'affected_user_id', 'id', 'users');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id', 'id', 'users');
    }

    public function subject()
    {
        return $this->morphTo(null, 'subject_type', 'subject_id', 'subjects');
    }
}
