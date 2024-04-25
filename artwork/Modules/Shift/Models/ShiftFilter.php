<?php

namespace Artwork\Modules\Shift\Models;

use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $name
 * @property int $user_id
 * @property string $created_at
 * @property string $updated_at
 */
class ShiftFilter extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'user_id'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function rooms(): BelongsToMany
    {
        return $this->belongsToMany(Room::class, 'room_shift_filter');
    }

    //@todo: fix phpcs error - refactor function name to eventTypes
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function event_types(): BelongsToMany
    {
        return $this->belongsToMany(EventType::class, 'event_type_shift_filter');
    }
}
