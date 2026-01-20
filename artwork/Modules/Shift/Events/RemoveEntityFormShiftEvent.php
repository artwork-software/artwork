<?php

namespace Artwork\Modules\Shift\Events;

use Artwork\Modules\Calendar\DTO\ShiftDTO;
use Artwork\Modules\Shift\Models\Shift;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RemoveEntityFormShiftEvent implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public $shift;
    public $roomId;
    public $entity;
    public $entityType;
    /**
     * Create a new event instance.
     */
    public function __construct(Shift $shift, int $roomId, string $entity, string $entityType)
    {
        $this->shift = $shift;
        $this->roomId = $roomId;
        $this->entity = $entity;
        $this->entityType = $entityType;
    }
    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('shift-plan.room.' . $this->roomId);
    }

    public function broadcastWith(): array
    {
        return [
            'shift' => ShiftDTO::fromModel($this->shift),
            'roomId' => $this->roomId,
            'daysOfShift' => $this->shift->getAttribute('days_of_shift'),
            'entity' => $this->entity,
            'entityType' => $this->entityType,
        ];
    }

    public function broadcastAs()
    {
        return 'shift-remove-entity';
    }
}
