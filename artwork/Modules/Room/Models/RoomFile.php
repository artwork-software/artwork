<?php

namespace Artwork\Modules\Room\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $name
 * @property string $basename
 * @property int $room_id
 * @property string $deleted_at
 * @property string $created_at
 * @property string $updated_at
 */
class RoomFile extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [
        'id'
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}
