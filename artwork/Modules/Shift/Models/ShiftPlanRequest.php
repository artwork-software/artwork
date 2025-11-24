<?php

namespace Artwork\Modules\Shift\Models;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $craft_id
 * @property int $week_number
 * @property int $year
 * @property string $status
 * @property int $requested_by_user_id
 * @property \DateTime|null $requested_at
 * @property int|null $reviewed_by_user_id
 * @property \DateTime|null $reviewed_at
 * @property string|null $review_comment
 * @property \Illuminate\Database\Eloquent\Collection|Shift[] $shifts
 * @property Craft $craft
 * @property User $requestedBy
 * @property User|null $reviewedBy
 */

class ShiftPlanRequest extends Model
{
    /** @use HasFactory<\Database\Factories\ShiftPlanRequestFactory> */
    use HasFactory;


    protected $fillable = [
        'craft_id',
        'week_number',
        'year',
        'status',
        'requested_by_user_id',
        'requested_at',
        'reviewed_by_user_id',
        'reviewed_at',
        'review_comment',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'reviewed_at'  => 'datetime',
    ];

    public function shifts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Shift::class, 'shift_plan_request_id', 'id', 'shifts');
    }

    /**
     * Historical requested shifts (snapshot/pivot)
     */
    public function requestedShifts(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            \Artwork\Modules\Shift\Models\Shift::class,
            'shift_plan_request_shifts',
            'shift_plan_request_id',
            'shift_id'
        )->withPivot(['snapshot'])->withTimestamps();
    }

    public function craft(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Craft::class, 'craft_id', 'id', 'crafts');
    }

    public function requestedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id', 'id', 'users');
    }

    public function reviewedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id', 'id', 'users');
    }
}
