<?php

namespace Artwork\Modules\Event\Events;

use Illuminate\Broadcasting\PrivateChannel;

trait PushesEventModification
{
    public function broadcastOn()
    {
        return new PrivateChannel('events');
    }
    
    public function broadcastWith(): array
    {
        return [
            'roomId' => $this->event->room_id,
            'dateData' => [
                'start' => $this->event->start_time->format('Y-m-d'),
                'end' => $this->event->is_series ?
                    $this->event->series->end_date->format('Y-m-d') :
                    $this->event->end_time->format('Y-m-d'),
            ]
        ];
    }
}