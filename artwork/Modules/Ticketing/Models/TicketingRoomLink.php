<?php

namespace Artwork\Modules\Ticketing\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Room\Models\Room;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ein artwork-Raum und seine Spielstätte in Artwork-Tickets.
 *
 * @property int $id
 * @property int $room_id
 * @property string $venue_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Room $room
 */
class TicketingRoomLink extends Model
{
    protected $table = 'ticketing_room_links';

    protected $fillable = [
        'room_id',
        'venue_id',
    ];

    /** @return BelongsTo<Room, $this> */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id', 'id', 'room');
    }
}
