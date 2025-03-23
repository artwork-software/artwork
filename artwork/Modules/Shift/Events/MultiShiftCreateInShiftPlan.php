<?php

namespace Artwork\Modules\Shift\Events;

use Artwork\Modules\Calendar\DTO\ShiftDTO;
use Artwork\Modules\Shift\Models\Shift;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class MultiShiftCreateInShiftPlan implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;


    public Collection $shifts;
    /**
     * Create a new event instance.
     */
    public function __construct(Collection $shifts)
    {
        $this->shifts = $shifts;
    }


    public function broadcastAs(): string
    {
        return 'multi-shifts-created';
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('shift-plan.multi-shifts'),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'shifts' => $this->shifts->map(fn(Shift $shift) => ShiftDTO::fromModel($shift->fresh())),
        ];
    }
}
