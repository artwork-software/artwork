<?php

namespace Artwork\Modules\Event\Events;

use Artwork\Modules\Event\Models\Event;
use Carbon\Carbon;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BulkEventChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Event $event;
    public string $action;

    /**
     * @param Event $event Die Event-Daten
     * @param string $action Typ der Änderung (created, updated, deleted)
     */
    public function __construct(Event $event, string $action)
    {
        $this->event = $event;
        $this->action = $action;
    }

    public function broadcastOn(): Channel
    {
        return new Channel('bulk.events');
    }

    public function broadcastAs(): string
    {
        return 'bulk.event.changed';
    }


    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'event' => [
                'id' => $this->event->id,
                'project_id' => $this->event->project_id,
                'type' => $this->event->event_type,
                'eventTypeName' => $this->event->event_type?->name ?? null,
                'name' => $this->event->name,
                'room' => $this->event->room,
                'roomName' => $this->event->room?->name ?? null,
                'roomPosition' => $this->event->room?->position ?? null,
                'day' => Carbon::parse($this->event->start_time)->format('Y-m-d'),
                'start_time' => $this->event->allDay ?  '' : Carbon::parse($this->event->start_time)->format('H:i'),
                'end_time' => $this->event->allDay ? '' : Carbon::parse($this->event->end_time)->format('H:i'),
                'allDay' => $this->event->allDay,
                'description' => $this->event->description,
                'is_planning' => $this->event->is_planning,
                'created_at' => $this->event->created_at,
                'updated_at' => $this->event->updated_at,
                'isNew' => $this->event->created_at->eq($this->event->updated_at),
            ],
            'action' => $this->action,
        ];
    }
}
