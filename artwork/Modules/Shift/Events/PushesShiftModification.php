<?php

namespace Artwork\Modules\Shift\Events;

use Illuminate\Broadcasting\PrivateChannel;

trait PushesShiftModification
{
    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('shifts');
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $event = $this->shift->event;
        return array_merge($this->shift->toArray(), ['event' => $event->toArray()]);
    }
}
