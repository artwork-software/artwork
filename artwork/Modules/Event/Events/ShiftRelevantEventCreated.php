<?php

namespace Artwork\Modules\Event\Events;

use Artwork\Modules\Event\Models\Event;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ShiftRelevantEventCreated implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public $event;
    public $projectId;

    public function __construct(Event $event, int $projectId)
    {
        $this->event = $event;
        $this->projectId = $projectId;
    }

    public function broadcastAs()
    {
        return 'shift.relevant.event.created';
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('project.' . $this->projectId);
    }

    public function broadcastWith(): array
    {
        return [
            'event' => $this->event,
        ];
    }
}