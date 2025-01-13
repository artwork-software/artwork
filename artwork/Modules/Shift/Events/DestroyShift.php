<?php

namespace Artwork\Modules\Shift\Events;

use Artwork\Modules\Shift\Models\Shift;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DestroyShift implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public Shift $shift;
    public int $roomId;

    public function __construct(Shift $shift, int $roomId)
    {
        $this->shift = $shift;
        $this->roomId = $roomId;
        Log::info('DestroyShift event created', ['shift_id' => $shift->id, 'room_id' => $this->roomId]);
    }

    /**
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'shift-destroyed.in.event';
    }

    public function broadcastOn(): PrivateChannel
    {
        Log::info('Broadcasting on channel', ['room_id' => $this->roomId]);
        return new PrivateChannel('destroy.events.room.' . $this->roomId);
    }

    public function broadcastWith(): array
    {
        return [
            'shift' => $this->shift,
            'room_id' => $this->roomId,
        ];
    }


}