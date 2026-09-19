<?php

namespace Tests\Feature\ExternalAccess\FeatureFlag;

use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Permission\Services\PermissionCatalogPresenter;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\ExternalAccess\ExternalAccessTestCase as TestCase;

final class FeatureDisabledTest extends TestCase
{
    #[Test]
    public function external_area_is_not_found_while_feature_is_disabled(): void
    {
        $this->enableExternalAccess(false);

        $this->get(route('external.login.form'))->assertNotFound();

        $external = ExternalAccess::factory()->active()->create();
        $this->actingAs($external, 'external');
        $this->get(route('external.dashboard'))->assertNotFound();
    }

    #[Test]
    public function external_area_is_reachable_when_enabled(): void
    {
        $this->get(route('external.login.form'))->assertOk();
    }

    #[Test]
    public function invitation_endpoints_are_forbidden_while_disabled(): void
    {
        $this->enableExternalAccess(false);
        $this->actingAsUserWith([PermissionEnum::INVITE_EXTERNAL]);

        $this->getJson(route('crm.externals.contact-types.index'))->assertForbidden();
        $this->postJson(route('crm.externals.invitations.store'), ['email' => 'x@example.test'])->assertForbidden();
    }

    #[Test]
    public function catalog_feature_flag_mirrors_setting(): void
    {
        $presenter = app(PermissionCatalogPresenter::class);
        $this->assertTrue($presenter->instanceState()['features']['external_access']);

        $this->enableExternalAccess(false);
        $this->assertFalse(app(PermissionCatalogPresenter::class)->instanceState()['features']['external_access']);
    }

    #[Test]
    public function shared_inertia_prop_reflects_setting(): void
    {
        $this->actingAsAdmin();

        $this->get(route('crm.index'))->assertInertia(fn ($page) => $page->where('externalAccessEnabled', true));

        $this->enableExternalAccess(false);
        $this->get(route('crm.index'))->assertInertia(fn ($page) => $page->where('externalAccessEnabled', false));
    }
}
