<?php

namespace Artwork\Modules\Event\Events;

use Artwork\Modules\Event\Models\Event;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Bus\Dispatchable;

class EventDeleted implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use PushesEventModification;

    public function __construct(public readonly int $roomId, public readonly array $dates)
    {
    }

    public function broadcastAs()
    {
        return 'event.deleted';
    }

    public function broadcastWith(): array
    {
        return [
            'roomId' => $this->roomId,
            'dateData' => $this->dates
        ];
    }
}