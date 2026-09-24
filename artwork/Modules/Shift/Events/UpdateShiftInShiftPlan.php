<?php

namespace Artwork\Modules\Shift\Events;

use Artwork\Modules\Calendar\DTO\ShiftDTO;
use Artwork\Modules\Shift\Events\Concerns\BuildsShiftBroadcastLookups;
use Artwork\Modules\Shift\Models\Shift;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UpdateShiftInShiftPlan implements ShouldBroadcastNow
{
    use BuildsShiftBroadcastLookups;
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public $shift;
    public $roomId;

    /**
     * Raum vor einem Raumwechsel: Ansichten, die den neuen Raum nicht geladen haben
     * (Raumfilter), erfahren nur über den alten Kanal, dass die Schicht dort weg ist.
     */
    public ?int $previousRoomId;

    public function __construct(Shift $shift, int $roomId, ?int $previousRoomId = null)
    {
        $this->shift = $shift;
        $this->roomId = $roomId;
        $this->previousRoomId = $previousRoomId !== null && $previousRoomId !== $roomId ? $previousRoomId : null;
    }

    public function broadcastAs()
    {
        return 'shift-created';
    }

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        $channels = [new PrivateChannel('shift-plan.room.' . $this->roomId)];

        if ($this->previousRoomId !== null) {
            $channels[] = new PrivateChannel('shift-plan.room.' . $this->previousRoomId);
        }

        return $channels;
    }

    public function broadcastWith(): array
    {
        $this->loadBroadcastRelations($this->shift);

        return [
            'shift' => ShiftDTO::fromModel($this->shift, $this->shift->project),
            'roomId' => $this->roomId,
            'previousRoomId' => $this->previousRoomId,
            'lookups' => $this->buildBroadcastLookups($this->shift),
        ];
    }
}
