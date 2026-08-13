<?php

namespace Artwork\Modules\Webhook\Jobs;

use Artwork\Modules\Webhook\Enums\WebhookDeliveryStatus;
use Artwork\Modules\Webhook\Models\WebhookDelivery;
use Artwork\Modules\Webhook\Services\WebhookSignature;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

/**
 * Stellt eine einzelne Zustellung zu.
 *
 * Läuft auf der Queue "webhooks" — der Worker muss sie also bedienen (siehe docker-compose.yml und
 * .ddev/config.yaml). Wiederholungen übernimmt die Queue über $tries und $backoff; der Job hält den
 * Zustand in der Zustellungszeile nach, damit er in der Oberfläche sichtbar wird.
 */
class SendWebhookJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 6;

    public int $timeout = 30;

    public function __construct(private readonly int $deliveryId)
    {
        $this->onQueue('webhooks');
    }

    /**
     * Wachsende Abstände: 1 min, 5 min, 30 min, 2 h, 6 h. Ein Empfänger, der ein Wartungsfenster
     * hat, bekommt die Zustellung so noch, ohne dass wir ihn in der Zwischenzeit zuschütten.
     *
     * @return list<int>
     */
    public function backoff(): array
    {
        return [60, 300, 1800, 7200, 21600];
    }

    public function handle(WebhookSignature $signature): void
    {
        $delivery = WebhookDelivery::with('endpoint')->find($this->deliveryId);

        if ($delivery === null || $delivery->status->isFinal()) {
            return;
        }

        $endpoint = $delivery->endpoint;

        if ($endpoint === null || !$endpoint->is_active) {
            $delivery->update([
                'status' => WebhookDeliveryStatus::EXHAUSTED,
                'error' => 'Endpoint is missing or inactive.',
                'next_retry_at' => null,
            ]);

            return;
        }

        $body = (string) json_encode([
            'event' => $delivery->event_name,
            'delivered_at' => now()->toIso8601String(),
            'data' => $delivery->payload,
        ], JSON_THROW_ON_ERROR);

        $timestamp = now()->getTimestamp();
        $attempt = $this->attempts();

        try {
            $response = Http::withHeaders([
                WebhookSignature::HEADER_SIGNATURE => $signature->sign($body, $endpoint->secret, $timestamp),
                WebhookSignature::HEADER_TIMESTAMP => (string) $timestamp,
                WebhookSignature::HEADER_EVENT => $delivery->event_name,
                WebhookSignature::HEADER_DELIVERY => (string) $delivery->getKey(),
                'Content-Type' => 'application/json',
            ])
                ->timeout(10)
                ->withBody($body, 'application/json')
                ->post($endpoint->url);
        } catch (Throwable $throwable) {
            $this->recordFailure($delivery, $attempt, null, $throwable->getMessage());

            throw $throwable;
        }

        if ($response->successful()) {
            $delivery->update([
                'attempt' => $attempt,
                'status' => WebhookDeliveryStatus::SUCCESS,
                'response_status' => $response->status(),
                'error' => null,
                'next_retry_at' => null,
                'delivered_at' => now(),
            ]);

            return;
        }

        $this->recordFailure($delivery, $attempt, $response->status(), $response->body());

        // Auslösen der Queue-Wiederholung. Ohne Ausnahme gälte der Job als erledigt.
        throw new RuntimeException(sprintf(
            'Webhook delivery %d failed with status %d.',
            $delivery->getKey(),
            $response->status()
        ));
    }

    /**
     * Wird von der Queue aufgerufen, wenn alle Versuche verbraucht sind.
     */
    public function failed(Throwable $throwable): void
    {
        WebhookDelivery::query()
            ->whereKey($this->deliveryId)
            ->update([
                'status' => WebhookDeliveryStatus::EXHAUSTED->value,
                'error' => $throwable->getMessage(),
                'next_retry_at' => null,
            ]);
    }

    private function recordFailure(
        WebhookDelivery $delivery,
        int $attempt,
        ?int $responseStatus,
        ?string $error
    ): void {
        $backoff = $this->backoff();
        $hasRetryLeft = $attempt < $this->tries;

        $delivery->update([
            'attempt' => $attempt,
            'status' => $hasRetryLeft ? WebhookDeliveryStatus::FAILED : WebhookDeliveryStatus::EXHAUSTED,
            'response_status' => $responseStatus,
            'error' => $error === null ? null : mb_substr($error, 0, 2000),
            'next_retry_at' => $hasRetryLeft
                ? now()->addSeconds($backoff[$attempt - 1] ?? end($backoff))
                : null,
        ]);
    }
}
