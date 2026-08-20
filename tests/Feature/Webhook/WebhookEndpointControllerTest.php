<?php

namespace Tests\Feature\Webhook;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Webhook\Enums\WebhookDeliveryStatus;
use Artwork\Modules\Webhook\Jobs\SendWebhookJob;
use Artwork\Modules\Webhook\Models\WebhookDelivery;
use Artwork\Modules\Webhook\Models\WebhookEndpoint;
use Illuminate\Support\Facades\Bus;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class WebhookEndpointControllerTest extends FeatureTestCase
{
    #[Test]
    public function creating_an_endpoint_requires_the_webhook_permission(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('webhooks.store'), $this->validPayload())
            ->assertForbidden();

        $this->assertSame(0, WebhookEndpoint::query()->count());
    }

    #[Test]
    public function an_endpoint_can_be_created_and_returns_its_secret_once(): void
    {
        $this->actingAsUserWith(PermissionEnum::WEBHOOKS_MANAGE->value);

        $response = $this->post(route('webhooks.store'), $this->validPayload());

        $response->assertRedirect();
        $response->assertSessionHas('webhookSecret');

        $endpoint = WebhookEndpoint::query()->firstOrFail();
        $this->assertSame('Ticketshop', $endpoint->name);
        $this->assertSame(['webhook.test'], $endpoint->subscribed_events);
        $this->assertTrue($endpoint->is_active);
    }

    #[Test]
    public function the_secret_is_hidden_when_the_model_is_serialised(): void
    {
        $this->actingAsUserWith(PermissionEnum::WEBHOOKS_MANAGE->value);
        $this->post(route('webhooks.store'), $this->validPayload());

        // Die Übersicht reicht Endpunkte ans Frontend — das Geheimnis darf dabei nicht mitfahren.
        $this->assertArrayNotHasKey('secret', WebhookEndpoint::query()->firstOrFail()->toArray());
    }

    #[Test]
    public function unknown_events_are_rejected(): void
    {
        $this->actingAsUserWith(PermissionEnum::WEBHOOKS_MANAGE->value);

        $this->post(route('webhooks.store'), [
            ...$this->validPayload(),
            'subscribed_events' => ['not.a.registered.event'],
        ])->assertSessionHasErrors('subscribed_events.0');
    }

    #[Test]
    public function plain_http_urls_are_rejected(): void
    {
        $this->actingAsUserWith(PermissionEnum::WEBHOOKS_MANAGE->value);

        $this->post(route('webhooks.store'), [
            ...$this->validPayload(),
            'url' => 'http://shop.example.test/hooks',
        ])->assertSessionHasErrors('url');
    }

    #[Test]
    public function an_endpoint_without_events_is_rejected(): void
    {
        $this->actingAsUserWith(PermissionEnum::WEBHOOKS_MANAGE->value);

        $this->post(route('webhooks.store'), [
            ...$this->validPayload(),
            'subscribed_events' => [],
        ])->assertSessionHasErrors('subscribed_events');
    }

    #[Test]
    public function an_exhausted_delivery_can_be_redelivered(): void
    {
        // FeatureTestCase faked bereits Queue UND Bus — Dispatchable-Jobs landen im Bus-Fake.
        $this->actingAsUserWith(PermissionEnum::WEBHOOKS_MANAGE->value);
        $delivery = $this->makeDelivery(WebhookDeliveryStatus::EXHAUSTED);

        $this->post(route('webhooks.deliveries.redeliver', $delivery->getKey()))
            ->assertRedirect()
            ->assertSessionHas('success');

        $delivery->refresh();
        $this->assertSame(WebhookDeliveryStatus::PENDING, $delivery->status);
        $this->assertSame(0, $delivery->attempt);
        Bus::assertDispatched(SendWebhookJob::class, 1);
    }

    #[Test]
    public function a_delivery_with_a_running_retry_chain_cannot_be_redelivered(): void
    {
        $this->actingAsUserWith(PermissionEnum::WEBHOOKS_MANAGE->value);

        // pending und failed sind nicht final: Der ursprüngliche Job liegt noch (released) in der
        // Queue — ein zweiter Dispatch würde doppelt zustellen und die Statusfelder überschreiben.
        foreach ([WebhookDeliveryStatus::PENDING, WebhookDeliveryStatus::FAILED] as $status) {
            $delivery = $this->makeDelivery($status, attempt: 2);

            $this->post(route('webhooks.deliveries.redeliver', $delivery->getKey()))
                ->assertRedirect()
                ->assertSessionHas('error');

            $delivery->refresh();
            $this->assertSame($status, $delivery->status);
            $this->assertSame(2, $delivery->attempt);
        }

        Bus::assertNotDispatched(SendWebhookJob::class);
    }

    private function makeDelivery(WebhookDeliveryStatus $status, int $attempt = 6): WebhookDelivery
    {
        $endpoint = WebhookEndpoint::create([
            'name' => 'Receiver ' . bin2hex(random_bytes(4)),
            'url' => 'https://shop.example.test/hooks',
            'secret' => 'secret-' . bin2hex(random_bytes(8)),
            'subscribed_events' => ['webhook.test'],
            'is_active' => true,
        ]);

        return WebhookDelivery::create([
            'webhook_endpoint_id' => $endpoint->getKey(),
            'event_name' => 'webhook.test',
            'payload' => ['foo' => 'bar'],
            'status' => $status,
            'attempt' => $attempt,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        return [
            'name' => 'Ticketshop',
            'url' => 'https://shop.example.test/hooks',
            'subscribed_events' => ['webhook.test'],
            'is_active' => true,
        ];
    }
}
