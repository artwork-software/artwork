<?php

namespace Artwork\Modules\Event\Events;

use Artwork\Modules\Calendar\DTO\BroadcastEventDTO;
use Artwork\Modules\Calendar\DTO\BroadcastEventDTOWithVerifications;
use Artwork\Modules\Event\Models\Event;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EventUpdated implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public $event;
    public $roomId;

    public function __construct(
        Event $event,
        int $roomId
    ) {
        $this->event = $event;
        $this->roomId = $roomId;
    }

    public function broadcastAs()
    {
        return 'event.updated';
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('event.room.' . $this->roomId);
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'event' => $this->event->is_planning
                ? BroadcastEventDTOWithVerifications::formModel($this->event)
                : BroadcastEventDTO::formModel($this->event),
        ];
    }
}
