<?php

namespace Artwork\Modules\Ticketing\Jobs;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Ticketing\Exceptions\TicketingConnectionException;
use Artwork\Modules\Ticketing\Services\TicketingConnectionService;
use Artwork\Modules\Ticketing\Services\TicketingReleaseService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Bringt einen freigegebenen Termin nach einer Kalenderänderung in tickets auf Stand — außerhalb
 * der Anfrage, damit der Kalender nie auf tickets wartet und nie an tickets scheitert. Ist tickets
 * nicht erreichbar, wird später erneut versucht; lehnt tickets ab, steht der Grund am Termin.
 */
class SyncTicketingEventJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public int $tries = 5;
    public int $timeout = 45;

    public function __construct(private readonly int $eventId)
    {
    }

    /** @return list<int> Abstände in Sekunden: 1 min, 5 min, 30 min, 2 h. */
    public function backoff(): array
    {
        return [60, 300, 1800, 7200];
    }

    public function handle(TicketingConnectionService $connections, TicketingReleaseService $releases): void
    {
        $event = Event::query()->with(['room', 'project', 'ticketingRelease'])->find($this->eventId);

        if (!$event || !$connections->current()) {
            return;
        }

        try {
            $releases->refresh($event);
        } catch (TicketingConnectionException $exception) {
            $event->ticketingRelease?->update(['sync_error' => $exception->getMessage()]);

            if ($exception->isTransient() && $this->attempts() < $this->tries) {
                $this->release($this->backoff()[$this->attempts() - 1] ?? 7200);

                return;
            }

            Log::warning('Artwork-Tickets sync gave up', [
                'event_id' => $this->eventId,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
