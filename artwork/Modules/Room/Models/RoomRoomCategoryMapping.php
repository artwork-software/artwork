<?php

namespace Artwork\Modules\Room\Models;

use Artwork\Core\Database\Models\Pivot;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Room\Models\RoomCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomRoomCategoryMapping extends Pivot
{
    use HasFactory;

    protected $fillable = [
        'room_id',
        'room_category_id'
    ];

    protected $table = 'room_room_category';

    /**
     * @return BelongsTo<Room, $this>
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * @return BelongsTo<RoomCategory, $this>
     */
    public function roomCategory(): BelongsTo
    {
        return $this->belongsTo(RoomCategory::class);
    }
}
