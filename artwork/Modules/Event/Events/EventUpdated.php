<?php

namespace Artwork\Modules\Event\Events;

use Artwork\Modules\Event\Models\Event;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Bus\Dispatchable;

class EventUpdated implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use PushesEventModification;

    public function __construct(public readonly Event $event)
    {
    }

    public function broadcastAs()
    {
        return 'event.updated';
    }
}