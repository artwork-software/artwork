<?php

namespace Artwork\Modules\Event\Events;

use Illuminate\Broadcasting\PrivateChannel;

trait PushesEventModification
{
    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('events');
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'roomId' => $this->roomId,
            'dateData' => [
                'start' => $this->start->format('Y-m-d'),
                'end' => $this->end->format('Y-m-d'),
            ]
        ];
    }
}
