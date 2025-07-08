<?php

namespace Artwork\Modules\WorkTime\Models;

use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkTimeChangeRequest extends Model
{
    /** @use HasFactory<\Database\Factories\WorkTimeChangeRequestFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'request_start_time',
        'request_end_time',
        'shift_id',
        'status',
        'comment',
        'requested_by',
        'approved_by',
    ];

    protected $casts = [
        'request_start_time' => 'datetime',
        'request_end_time' => 'datetime',
        'status' => 'string',
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id',
            'id',
            'work_time_change_requests'
        );
    }

    public function shift(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(
            Shift::class,
            'shift_id',
            'id',
            'work_time_change_requests'
        );
    }

    public function requestedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'requested_by',
            'id',
            'work_time_change_requests'
        );
    }

    public function approvedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'approved_by',
            'id',
            'work_time_change_requests'
        );
    }
}
