<?php

namespace Artwork\Modules\Shift\Models;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

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
    use Prunable;


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
        'rejected_days',
        'rejected_shifts',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'reviewed_at'  => 'datetime',
        'rejected_days' => 'array',
        'rejected_shifts' => 'array',
    ];

    public function prunable(): Builder
    {
        $twelveMonthsAgo = Carbon::now()->subMonths(12);
        $cutoffYear = (int) $twelveMonthsAgo->format('o');
        $cutoffWeek = (int) $twelveMonthsAgo->format('W');

        return static::query()->where(function ($q) use ($cutoffYear, $cutoffWeek) {
            $q->where('year', '<', $cutoffYear)
                ->orWhere(function ($q2) use ($cutoffYear, $cutoffWeek) {
                    $q2->where('year', '=', $cutoffYear)
                        ->where('week_number', '<', $cutoffWeek);
                });
        });
    }

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
