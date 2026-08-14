<?php

namespace Artwork\Modules\Webhook\Services;

use Artwork\Modules\Webhook\Enums\WebhookDeliveryStatus;
use Artwork\Modules\Webhook\Jobs\SendWebhookJob;
use Artwork\Modules\Webhook\Models\WebhookDelivery;
use Artwork\Modules\Webhook\Models\WebhookEndpoint;
use Illuminate\Support\Collection;

class WebhookDispatcher
{
    /**
     * @param array<string, mixed> $payload
     * @return Collection<int, WebhookDelivery> Die angelegten Zustellungen, eine je Empfänger.
     */
    public function dispatch(string $eventName, array $payload): Collection
    {
        return $this->endpointsFor($eventName)
            ->map(function (WebhookEndpoint $endpoint) use ($eventName, $payload): WebhookDelivery {
                $delivery = WebhookDelivery::create([
                    'webhook_endpoint_id' => $endpoint->getKey(),
                    'event_name' => $eventName,
                    'payload' => $payload,
                    'status' => WebhookDeliveryStatus::PENDING,
                ]);

                SendWebhookJob::dispatch($delivery->getKey());

                return $delivery;
            });
    }

    /**
     * @return Collection<int, WebhookEndpoint>
     */
    private function endpointsFor(string $eventName): Collection
    {
        return WebhookEndpoint::query()
            ->where('is_active', true)
            ->get()
            // Die Filterung läuft in PHP statt per JSON-Abfrage: Die Zahl der Endpunkte liegt im
            // einstelligen Bereich, und so bleibt das Verhalten über MySQL und MariaDB gleich.
            ->filter(fn (WebhookEndpoint $endpoint): bool => $endpoint->isSubscribedTo($eventName))
            ->values();
    }
}
