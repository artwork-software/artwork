<?php

namespace Artwork\Modules\Ticketing\Jobs;

use Artwork\Modules\Ticketing\Exceptions\TicketingConnectionException;
use Artwork\Modules\Ticketing\Services\TicketingConnectionService;
use Artwork\Modules\Ticketing\Services\TicketingCustomerSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Holt die Käufer aus Artwork-Tickets ins CRM — nachts und auf Knopfdruck. Höchstens ein Lauf auf
 * einmal; ist tickets nicht erreichbar oder am Limit, macht ein späterer Versuch an derselben Seite weiter.
 */
class SyncTicketingCustomersJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public int $tries = 5;
    public int $timeout = 600;
    public int $uniqueFor = 3600;

    /** @return list<int> Abstände in Sekunden: 1 min, 5 min, 30 min, 2 h. */
    public function backoff(): array
    {
        return [60, 300, 1800, 7200];
    }

    public function handle(TicketingConnectionService $connections, TicketingCustomerSyncService $customers): void
    {
        $connection = $connections->current();

        if (!$connection) {
            return;
        }

        try {
            $customers->sync($connection);
        } catch (TicketingConnectionException $exception) {
            $connection->update(['customers_sync_error' => $exception->getMessage()]);

            if ($exception->isTransient() && $this->attempts() < $this->tries) {
                $this->release($this->backoff()[$this->attempts() - 1] ?? 7200);

                return;
            }

            Log::warning('Artwork-Tickets customer sync gave up', ['message' => $exception->getMessage()]);
        }
    }
}
