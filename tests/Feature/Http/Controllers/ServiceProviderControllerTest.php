<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * ServiceProviderController uses Meilisearch in some methods.
 * We test only methods that don't trigger search().
 */
final class ServiceProviderControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_create_service_provider(): void
    {
        $this->post(route('service_provider.add'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_create_service_provider(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('service_provider.add'), [
            'provider_name' => 'Test Company GmbH',
        ]);

        // Inertia::location returns a 409 Conflict with X-Inertia-Location header
        $this->assertContains($response->getStatusCode(), [200, 302, 409]);
        $this->assertDatabaseHas('service_providers', [
            'provider_name' => 'Test Company GmbH',
        ]);
    }

    #[Test]
    public function store_validates_required_fields(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('service_provider.add'), []);

        $response->assertSessionHasErrors(['provider_name']);
    }

    #[Test]
    public function admin_can_update_service_provider(): void
    {
        $this->actingAsAdmin();
        $sp = ServiceProvider::factory()->create();

        $response = $this->patch(route('service_provider.update', $sp), [
            'provider_name' => 'New Name',
            'email' => 'newname@example.com',
            'phone_number' => '123',
            'street' => 'Some St',
            'zip_code' => '12345',
            'location' => 'City',
            'note' => 'note',
            'type_of_provider' => 'work',
        ]);

        $response->assertOk();
        $this->assertSame('New Name', $sp->fresh()->provider_name);
    }

    #[Test]
    public function admin_can_destroy_service_provider(): void
    {
        $this->actingAsAdmin();
        $sp = ServiceProvider::factory()->create();

        $response = $this->delete(route('service_provider.destroy', $sp));

        $response->assertRedirect();
        $this->assertDatabaseMissing('service_providers', ['id' => $sp->id]);
    }

    #[Test]
    public function guest_cannot_destroy_service_provider(): void
    {
        $sp = ServiceProvider::factory()->create();
        $this->delete(route('service_provider.destroy', $sp))
            ->assertRedirect(route('login'));
    }
}
