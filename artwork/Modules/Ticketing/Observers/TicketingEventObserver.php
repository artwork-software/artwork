<?php

namespace Artwork\Modules\Ticketing\Observers;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Ticketing\Services\TicketingReleaseService;

/** Ein freigegebener Termin bleibt in tickets auf dem Stand des Kalenders: verschoben heißt neu geschickt, gelöscht heißt zurückgezogen. */
class TicketingEventObserver
{
    public function __construct(private readonly TicketingReleaseService $releases)
    {
    }

    public function updated(Event $event): void
    {
        if ($event->wasChanged(['start_time', 'end_time', 'admission_time', 'room_id'])) {
            $this->releases->refresh($event);
        }
    }

    public function deleting(Event $event): void
    {
        $this->releases->withdraw(collect([$event]));
    }
}
