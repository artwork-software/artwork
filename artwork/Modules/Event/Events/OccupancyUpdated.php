<?php

namespace Artwork\Modules\Event\Events;

use Artwork\Modules\Event\Models\Event;
use Carbon\Carbon;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Sammel-Signal für Termin-Änderungen ohne Live-Update je Termin (Massenlöschen, Wiederherstellen,
 * KI-Stapel, Serien-Turnus): offene Kalender laden die sichtbaren Monate im Zeitraum neu. Nur Datumsgrenzen,
 * keine Termindaten: was jemand sehen darf, entscheidet der Nachlade-Request mit den eigenen Rechten.
 * Ohne Zeitraum laden die Kalender ihre sichtbaren Monate neu.
 */
class OccupancyUpdated implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly ?string $start = null,
        public readonly ?string $end = null,
    ) {
    }

    /**
     * Zeitraum aller betroffenen Termine (frühester Beginn bis spätestes Ende, Y-m-d).
     *
     * @param iterable<Event> $events
     */
    public static function forEvents(iterable $events): self
    {
        $start = null;
        $end = null;
        foreach ($events as $event) {
            $eventStart = $event->start_time ? Carbon::parse($event->start_time)->format('Y-m-d') : null;
            $eventEnd = $event->end_time ? Carbon::parse($event->end_time)->format('Y-m-d') : $eventStart;
            if ($eventStart !== null && ($start === null || $eventStart < $start)) {
                $start = $eventStart;
            }
            if ($eventEnd !== null && ($end === null || $eventEnd > $end)) {
                $end = $eventEnd;
            }
        }

        return new self($start, $end ?? $start);
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('events');
    }

    public function broadcastAs(): string
    {
        return 'occupancy.updated';
    }

    /**
     * @return array{start: string|null, end: string|null}
     */
    public function broadcastWith(): array
    {
        return ['start' => $this->start, 'end' => $this->end];
    }
}
