<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * NOTE: The `service_provider_contacts` table is not migrated in the test database
 * (the model assumes the table exists, but it doesn't in this codebase). We only
 * verify auth boundaries here (smoke-only).
 */
final class ServiceProviderContactsControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_store(): void
    {
        $sp = ServiceProvider::factory()->create();
        $this->post(route('service-provider.contact.store', $sp), [])
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function controller_resolves_from_container(): void
    {
        $this->assertInstanceOf(
            \App\Http\Controllers\ServiceProviderContactsController::class,
            $this->app->make(\App\Http\Controllers\ServiceProviderContactsController::class)
        );
    }
}
