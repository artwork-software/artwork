<?php

namespace Artwork\Modules\Filter\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Area\Models\Area;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\RoomAttribute\Models\RoomAttribute;
use Artwork\Modules\RoomCategory\Models\RoomCategory;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $name
 * @property int $user_id
 * @property bool $isLoud
 * @property bool $isNotLoud
 * @property bool $hasAudience
 * @property bool $hasNoAudience
 * @property bool $adjoiningNoAudience
 * @property bool $adjoiningNotLoud
 * @property bool $allDayFree
 * @property bool $showAdjoiningRooms
 * @property string $created_at
 * @property string $updated_at
 */
class Filter extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'isLoud',
        'isNotLoud',
        'hasAudience',
        'hasNoAudience',
        'adjoiningNoAudience',
        'adjoiningNotLoud',
        'allDayFree',
        'showAdjoiningRooms',
        'user_id'
    ];

    protected $casts = [
        'isLoud' => 'boolean',
        'isNotLoud' => 'boolean',
        'hasAudience' => 'boolean',
        'hasNoAudience' => 'boolean',
        'adjoiningNoAudience' => 'boolean',
        'adjoiningNotLoud' => 'boolean',
        'allDayFree' => 'boolean',
        'showAdjoiningRooms' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id', 'users');
    }

    //@todo: fix phpcs error - refactor function name to roomCategories
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function room_categories(): BelongsToMany
    {
        return $this->belongsToMany(RoomCategory::class, 'filter_room_category');
    }

    //@todo: fix phpcs error - refactor function name to roomAttributes
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function room_attributes(): BelongsToMany
    {
        return $this->belongsToMany(RoomAttribute::class, 'filter_room_attribute');
    }

    public function rooms(): BelongsToMany
    {
        return $this->belongsToMany(Room::class, 'filter_room');
    }

    public function areas(): BelongsToMany
    {
        return $this->belongsToMany(Area::class, 'area_filter');
    }

    //@todo: fix phpcs error - refactor function name to eventTypes
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function event_types(): BelongsToMany
    {
        return $this->belongsToMany(EventType::class, 'event_type_filter');
    }
}
