<?php

namespace Artwork\Modules\RoomCategory\Models;

use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\RoomRoomCategoryMapping\Models\RoomRoomCategoryMapping;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $name
 * @property string $created_at
 * @property string $updated_at
 */
class RoomCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name'
    ];

    public function rooms(): BelongsToMany
    {
        return $this->belongsToMany(Room::class)->using(RoomRoomCategoryMapping::class);
    }
}
