<?php

namespace Tests\Feature\Webhook;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Webhook\Models\WebhookEndpoint;
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
