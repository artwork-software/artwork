<?php

namespace Tests\Feature\ExternalAccess\Middleware;

use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Models\ExternalAccessScope;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\ExternalAccess\ExternalAccessTestCase as TestCase;

final class EnsureCrmAccessActiveTest extends TestCase
{
    #[Test]
    public function tab_only_external_cannot_open_crm_routes_after_crm_expiry(): void
    {
        $external = ExternalAccess::factory()->crmAccessExpired()->create();
        ExternalAccessScope::factory()->create(['external_access_id' => $external->id]);
        $this->actingAs($external, 'external');

        // Login bleibt möglich (Tab-Scope aktiv) …
        $this->get(route('external.dashboard'))->assertOk();
        // … aber die CRM-Seiten sind gesperrt
        $this->get(route('external.crm.show'))->assertRedirect(route('external.dashboard'));
        $this->post(route('external.crm.submit'), ['values' => []])->assertRedirect(route('external.dashboard'));
        $this->getJson(route('external.crm.submission-status'))->assertForbidden();
    }

    #[Test]
    public function external_with_active_crm_access_can_open_crm_page(): void
    {
        $external = ExternalAccess::factory()->active()->create();
        $this->actingAs($external, 'external');

        $this->get(route('external.crm.show'))->assertOk();
    }

    #[Test]
    public function shared_props_expose_crm_access_state(): void
    {
        $external = ExternalAccess::factory()->crmAccessExpired()->create();
        ExternalAccessScope::factory()->create(['external_access_id' => $external->id]);
        $this->actingAs($external, 'external');

        $this->get(route('external.dashboard'))
            ->assertInertia(fn ($page) => $page->where('crm_access_active', false));
    }
}
