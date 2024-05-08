<?php

namespace Artwork\Modules\RoomAttribute\Models;

use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\RoomRoomAttributeMapping\Models\RoomRoomAttributeMapping;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $name
 * @property string $created_at
 * @property string $updated_at
 */
class RoomAttribute extends Model
{
    use HasFactory;

    protected $fillable = [
        'name'
    ];

    public function rooms(): BelongsToMany
    {
        return $this->belongsToMany(Room::class)->using(RoomRoomAttributeMapping::class);
    }
}
