<?php

namespace Artwork\Modules\User\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $name
 * @property int $user_id
 * @property string $filter_type
 * @property array $event_type_ids
 * @property array $room_ids
 * @property array $area_ids
 * @property array $room_attribute_ids
 * @property array $room_category_ids
 * @property array $event_property_ids
 * @property array $craft_ids
 * * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * * @property User $user
 * @method static Builder|UserFilterTemplate shiftFilter()
 */
class UserFilterTemplate extends Model
{
    /** @use HasFactory<\Database\Factories\UserFilterTemplateFactory> */
    use HasFactory;

    /** @use HasFactory<\Database\Factories\UserFilterFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'user_id',
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
        return $this->belongsTo(User::class, 'user_id', 'id', 'user');
    }

    public function scopeShiftFilter(Builder $query): Builder
    {
        return $query->where('filter_type', 'shift_filter');
    }

    public function scopeCalendarFilter(Builder $query): Builder
    {
        return $query->where('filter_type', 'calendar_filter');
    }
}
