<?php

namespace Artwork\Modules\Event\Events;

use Artwork\Modules\Event\Events\Concerns\HasPlanningCompanionBroadcast;
use Artwork\Modules\Event\Events\Concerns\SplitsPlanningBroadcast;
use Artwork\Modules\Event\Models\Event;
use Carbon\Carbon;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BulkEventChanged implements ShouldBroadcastNow, HasPlanningCompanionBroadcast
{
    use Dispatchable;
    use SplitsPlanningBroadcast;
    use InteractsWithSockets;
    use SerializesModels;

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

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel($this->onPlanningChannel ? 'bulk.events.planning' : 'bulk.events');
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
            // Nur was die Bulk-Zeile liest – das Raum-Model brachte über $with den anlegenden Nutzer samt
            // E-Mail und die Raumadmins mit, an alle Abonnent:innen des offenen Kanals
            'room' => $event->room?->only(['id', 'name', 'position']),
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
        if ($this->sendsRedactedPayload()) {
            return [
                'event' => [
                    'id' => $this->event->id,
                    'project_id' => $this->event->project_id,
                    'is_planning' => true,
                    'redacted' => true,
                ],
                'action' => $this->action,
            ];
        }

        return [
            'event' => self::eventPayload($this->event),
            'action' => $this->action,
        ];
    }
}
