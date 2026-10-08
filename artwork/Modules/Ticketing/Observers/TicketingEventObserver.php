<?php

namespace Artwork\Modules\Ticketing\Observers;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Ticketing\Jobs\SyncTicketingEventJob;
use Artwork\Modules\Ticketing\Models\TicketingEventRelease;
use Artwork\Modules\Ticketing\Services\TicketingConnectionService;
use Artwork\Modules\Ticketing\Services\TicketingLock;

/**
 * Ein freigegebener Termin bleibt in tickets auf dem Stand des Kalenders: verschoben heißt neu geschickt,
 * und zwar aus der Warteschlange, damit der Kalender nie auf tickets wartet. Verschieben braucht Recht und
 * Bestätigung; gelöscht wird er nicht, das geht nur über das Zurückziehen in der Ticketing-Komponente.
 * Ohne aktive Verbindung passiert nichts.
 */
class TicketingEventObserver
{
    private const MOVED = ['start_time', 'end_time', 'admission_time', 'room_id'];

    public function __construct(
        private readonly TicketingConnectionService $connections,
        private readonly TicketingLock $lock,
    ) {
    }

    public function updating(Event $event): void
    {
        if ($event->isDirty(self::MOVED)) {
            $this->lock->assertEventMovable($event);
        }
    }

    public function updated(Event $event): void
    {
        $moved = $event->wasChanged(self::MOVED);

        if (!$moved || !$this->connections->isActive()) {
            return;
        }

        $released = TicketingEventRelease::query()
            ->where('event_id', $event->id)
            ->where('state', TicketingEventRelease::STATE_RELEASED)
            ->exists();

        if ($released) {
            SyncTicketingEventJob::dispatch($event->id)->afterCommit();
        }
    }

    public function deleting(Event $event): void
    {
        $this->lock->assertEventDeletable($event);
    }
}
