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
     * Einheitliche Event-Payload für Broadcast UND JSON-Responses der Bulk-Endpunkte —
     * der eigene Client aktualisiert seine Liste aus der Response, andere Clients
     * über den Broadcast; beide müssen dieselbe Form sehen.
     *
     * @return array<string, mixed>
     */
    public static function eventPayload(Event $event): array
    {
        return [
            'id' => $event->id,
            'project_id' => $event->project_id,
            'type' => $event->event_type,
            'status' => $event->eventStatus,
            'eventTypeName' => $event->event_type?->name ?? null,
            'name' => $event->name,
            'room' => $event->room,
            'roomName' => $event->room?->name ?? null,
            'roomPosition' => $event->room?->position ?? null,
            'day' => Carbon::parse($event->start_time)->format('Y-m-d'),
            'end_day' => Carbon::parse($event->end_time)->format('Y-m-d'),
            'start_time' => $event->allDay ?  '' : Carbon::parse($event->start_time)->format('H:i'),
            'end_time' => $event->allDay ? '' : Carbon::parse($event->end_time)->format('H:i'),
            'admission_time' => $event->admission_time ? substr($event->admission_time, 0, 5) : null,
            'allDay' => $event->allDay,
            'description' => $event->description,
            'is_planning' => $event->is_planning,
            'created_at' => $event->created_at,
            'updated_at' => $event->updated_at,
            'isNew' => $event->created_at->eq($event->updated_at),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'event' => self::eventPayload($this->event),
            'action' => $this->action,
        ];
    }
}
