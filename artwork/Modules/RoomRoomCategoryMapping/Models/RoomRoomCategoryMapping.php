<?php

namespace Artwork\Modules\RoomRoomCategoryMapping\Models;

use Artwork\Core\Database\Models\Pivot;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\RoomCategory\Models\RoomCategory;
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

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function roomCategory(): BelongsTo
    {
        return $this->belongsTo(RoomCategory::class);
    }
}
