<?php

namespace Artwork\Modules\User\Models;

use Artwork\Modules\User\Enums\UserFilterTypes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon $end_date
 * @property string $filter_type
 * @property array $event_type_ids
 * @property array $room_ids
 * @property array $area_ids
 * @property array $room_attribute_ids
 * @property array $room_category_ids
 * @property array $event_property_ids
 * @property array $craft_ids
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon $deleted_at
 * @property User $user
 * @method static Builder|UserFilter shiftFilter()
 * @method static Builder|UserFilter calendarFilter()
 * @method static Builder|UserFilter planningCalendarFilter()
 */

class UserFilter extends Model
{
    /** @use HasFactory<\Database\Factories\UserFilterFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'start_date',
        'end_date',
        'filter_type',
        'event_type_ids',
        'room_ids',
        'area_ids',
        'room_attribute_ids',
        'room_category_ids',
        'event_property_ids',
        'craft_ids',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'event_type_ids' => 'array',
        'room_ids' => 'array',
        'area_ids' => 'array',
        'room_attribute_ids' => 'array',
        'room_category_ids' => 'array',
        'event_property_ids' => 'array',
        'craft_ids' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id', 'user_filters');
    }

    public function scopeShiftFilter(Builder $query): Builder
    {
        return $query->where('filter_type', UserFilterTypes::SHIFT_FILTER->value);
    }

    public function scopeCalendarFilter(Builder $query): Builder
    {
        return $query->where('filter_type', UserFilterTypes::CALENDAR_FILTER->value);
    }
    public function scopePlanningCalendarFilter(Builder $query): Builder
    {
        return $query->where('filter_type', UserFilterTypes::PLANNING_FILTER->value);
    }
}
