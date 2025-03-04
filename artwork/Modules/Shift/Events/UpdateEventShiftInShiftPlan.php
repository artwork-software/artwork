<?php

namespace Artwork\Modules\Shift\Events;

use Artwork\Modules\Calendar\DTO\ShiftDTO;
use Artwork\Modules\Shift\Models\Shift;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UpdateEventShiftInShiftPlan implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public $shift;
    public $roomId;

    public function __construct(Shift $shift, int $roomId)
    {
        $this->shift = $shift;
        $this->roomId = $roomId;
    }

    public function broadcastAs()
    {
        return 'shift-updated.in.event';
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('shift-plan.room.' . $this->roomId);
    }

    public function broadcastWith(): array
    {
        return [
            'shift' => ShiftDTO::fromModel($this->shift),
            'roomId' => $this->roomId,
            'daysOfShift' => $this->shift->getAttribute('days_of_shift'), // Tag der Schicht
        ];
    }
}
