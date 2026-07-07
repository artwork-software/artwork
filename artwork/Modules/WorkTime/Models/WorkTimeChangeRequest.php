<?php

namespace Artwork\Modules\WorkTime\Models;

use Artwork\Core\Casts\TimeWithoutSeconds;
use Artwork\Core\Casts\TranslatedDateTimeCast;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property \Artwork\Core\Casts\TimeWithoutSeconds $request_start_time
 * @property \Artwork\Core\Casts\TimeWithoutSeconds $request_end_time
 * @property int|null $shift_id
 * @property int|null $craft_id
 * @property string $status
 * @property string|null $request_comment
 * @property string|null $decline_comment
 * @property int|null $requested_by
 * @property int|null $approved_by
 * @property int|null $declined_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Artwork\Modules\User\Models\User $user
 * @property-read \Artwork\Modules\Shift\Models\Shift|null $shift
 * @property-read \Artwork\Modules\Craft\Models\Craft|null $craft
 * @property-read \Artwork\Modules\User\Models\User|null $requestedBy
 * @property-read \Artwork\Modules\User\Models\User|null $approvedBy
 * @property-read \Artwork\Modules\User\Models\User|null $declinedBy
 */
class WorkTimeChangeRequest extends Model
{
    /** @use HasFactory<\Database\Factories\WorkTimeChangeRequestFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'request_start_time',
        'request_end_time',
        'shift_id',
        'craft_id',
        'status',
        'request_comment',
        'decline_comment',
        'requested_by',
        'approved_by',
        'declined_by',
        'request_end_date',
    ];

    protected $casts = [
        'request_start_time' => TimeWithoutSeconds::class,
        'request_end_time' => TimeWithoutSeconds::class,
        'status' => 'string',
        'created_at' => TranslatedDateTimeCast::class,
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
        )->without(['freelancer', 'users', 'service_provider', 'craft']);
    }

    public function craft(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(
            Craft::class,
            'craft_id',
            'id',
            'work_time_change_requests'
        )->with(['craftShiftPlaner']);
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

    public function declinedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'declined_by',
            'id',
            'work_time_change_requests'
        );
    }
}
