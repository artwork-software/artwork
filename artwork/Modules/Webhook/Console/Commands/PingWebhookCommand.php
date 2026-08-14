<?php

namespace Artwork\Modules\Webhook\Console\Commands;

use Artwork\Modules\Webhook\Services\WebhookDispatcher;
use Illuminate\Console\Command;

/**
 * Prüft die Webhook-Strecke Ende zu Ende, ohne dass ein Fachmodul beteiligt sein muss:
 * Endpunkt anlegen, "webhook.test" abonnieren, diesen Befehl ausführen, Zustellung beobachten.
 */
class PingWebhookCommand extends Command
{
    protected $signature = 'webhooks:ping';

    protected $description = 'Dispatches a test webhook to every endpoint subscribed to webhook.test';

    public function handle(WebhookDispatcher $dispatcher): int
    {
        $deliveries = $dispatcher->dispatch('webhook.test', [
            'message' => 'This is a test delivery from artwork.',
            'instance' => config('app.url'),
        ]);

        if ($deliveries->isEmpty()) {
            $this->warn('No active endpoint is subscribed to "webhook.test" — nothing was sent.');

            return self::SUCCESS;
        }

        $this->info(sprintf('Queued %d delivery/deliveries.', $deliveries->count()));

        if (config('queue.default') === 'sync') {
            $this->warn('QUEUE_CONNECTION is "sync": the delivery ran inside this command, without retries.');
        } else {
            $this->line('A worker consuming the "webhooks" queue will perform the delivery.');
        }

        return self::SUCCESS;
    }
}
