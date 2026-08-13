<?php

namespace Tests\Feature\Webhook;

use Artwork\Modules\Webhook\Enums\WebhookDeliveryStatus;
use Artwork\Modules\Webhook\Jobs\SendWebhookJob;
use Artwork\Modules\Webhook\Models\WebhookDelivery;
use Artwork\Modules\Webhook\Models\WebhookEndpoint;
use Artwork\Modules\Webhook\Services\WebhookDispatcher;
use Artwork\Modules\Webhook\Services\WebhookSignature;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\Feature\FeatureTestCase;

final class WebhookDeliveryTest extends FeatureTestCase
{
    #[Test]
    public function only_subscribed_and_active_endpoints_receive_a_delivery(): void
    {
        $subscribed = $this->makeEndpoint(['webhook.test']);
        $this->makeEndpoint(['something.else']);
        $this->makeEndpoint(['webhook.test'], isActive: false);

        $deliveries = app(WebhookDispatcher::class)->dispatch('webhook.test', ['foo' => 'bar']);

        $this->assertCount(1, $deliveries);
        $this->assertSame($subscribed->getKey(), $deliveries->first()->webhook_endpoint_id);
        $this->assertSame(1, WebhookDelivery::query()->count());
    }

    #[Test]
    public function dispatching_an_unsubscribed_event_creates_nothing(): void
    {
        $this->makeEndpoint(['webhook.test']);

        $deliveries = app(WebhookDispatcher::class)->dispatch('nobody.listens', []);

        $this->assertTrue($deliveries->isEmpty());
        $this->assertSame(0, WebhookDelivery::query()->count());
    }

    #[Test]
    public function a_successful_delivery_is_signed_and_marked_as_delivered(): void
    {
        Http::fake(['*' => Http::response('', 200)]);

        $endpoint = $this->makeEndpoint(['webhook.test']);
        $delivery = $this->makeDelivery($endpoint);

        $this->runJob($delivery);

        $delivery->refresh();
        $this->assertSame(WebhookDeliveryStatus::SUCCESS, $delivery->status);
        $this->assertSame(200, $delivery->response_status);
        $this->assertNotNull($delivery->delivered_at);

        // Gegenprobe aus Empfängersicht: Signatur über "<timestamp>.<body>" nachrechnen.
        Http::assertSent(function ($request) use ($endpoint): bool {
            $timestamp = (int) $request->header(WebhookSignature::HEADER_TIMESTAMP)[0];
            $signature = $request->header(WebhookSignature::HEADER_SIGNATURE)[0];

            return app(WebhookSignature::class)->verify(
                $request->body(),
                $endpoint->secret,
                $timestamp,
                $signature
            );
        });
    }

    #[Test]
    public function the_payload_carries_the_event_name_and_data(): void
    {
        Http::fake(['*' => Http::response('', 200)]);

        $endpoint = $this->makeEndpoint(['webhook.test']);
        $delivery = $this->makeDelivery($endpoint, ['answer' => 42]);

        $this->runJob($delivery);

        Http::assertSent(function ($request): bool {
            $body = json_decode($request->body(), true);

            return $body['event'] === 'webhook.test'
                && $body['data'] === ['answer' => 42]
                && isset($body['delivered_at']);
        });
    }

    #[Test]
    public function a_failing_receiver_marks_the_delivery_and_schedules_a_retry(): void
    {
        Http::fake(['*' => Http::response('kaputt', 500)]);

        $endpoint = $this->makeEndpoint(['webhook.test']);
        $delivery = $this->makeDelivery($endpoint);

        // Der Job muss werfen, sonst gilt er der Queue als erledigt und es gäbe keinen zweiten Versuch.
        $this->expectException(RuntimeException::class);

        try {
            $this->runJob($delivery);
        } finally {
            $delivery->refresh();
            $this->assertSame(WebhookDeliveryStatus::FAILED, $delivery->status);
            $this->assertSame(500, $delivery->response_status);
            $this->assertNotNull($delivery->next_retry_at);
        }
    }

    #[Test]
    public function an_inactive_endpoint_exhausts_the_delivery_without_calling_out(): void
    {
        Http::fake();

        $endpoint = $this->makeEndpoint(['webhook.test']);
        $delivery = $this->makeDelivery($endpoint);
        $endpoint->update(['is_active' => false]);

        $this->runJob($delivery);

        $delivery->refresh();
        $this->assertSame(WebhookDeliveryStatus::EXHAUSTED, $delivery->status);
        Http::assertNothingSent();
    }

    #[Test]
    public function the_secret_is_encrypted_at_rest(): void
    {
        $endpoint = $this->makeEndpoint(['webhook.test']);

        $stored = DB::table('webhook_endpoints')
            ->where('id', $endpoint->getKey())
            ->value('secret');

        $this->assertNotSame($endpoint->secret, $stored);
        $this->assertStringNotContainsString($endpoint->secret, (string) $stored);
    }

    #[Test]
    public function the_job_runs_on_the_webhooks_queue(): void
    {
        // Der Worker muss diese Queue bedienen — siehe docker-compose.yml und .ddev/config.yaml.
        $this->assertSame('webhooks', (new SendWebhookJob(1))->queue);
    }

    /**
     * Führt den Job direkt aus, nicht über die Queue. attempts() liefert dabei 1, was dem ersten
     * Zustellversuch entspricht.
     */
    private function runJob(WebhookDelivery $delivery): void
    {
        (new SendWebhookJob($delivery->getKey()))->handle(app(WebhookSignature::class));
    }

    /**
     * @param list<string> $events
     */
    private function makeEndpoint(array $events, bool $isActive = true): WebhookEndpoint
    {
        return WebhookEndpoint::create([
            'name' => 'Receiver ' . bin2hex(random_bytes(4)),
            'url' => 'https://shop.example.test/hooks',
            'secret' => 'secret-' . bin2hex(random_bytes(8)),
            'subscribed_events' => $events,
            'is_active' => $isActive,
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function makeDelivery(WebhookEndpoint $endpoint, array $payload = ['foo' => 'bar']): WebhookDelivery
    {
        return WebhookDelivery::create([
            'webhook_endpoint_id' => $endpoint->getKey(),
            'event_name' => 'webhook.test',
            'payload' => $payload,
            'status' => WebhookDeliveryStatus::PENDING,
        ]);
    }
}
