<?php

namespace Tests\Feature\Http\Controllers;

use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class ToolSettingsBrandingControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_view_branding(): void
    {
        $this->get(route('tool.branding'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_view_branding(): void
    {
        $this->actingAsAdmin();

        $response = $this->get(route('tool.branding'));

        $response->assertOk();
    }

    #[Test]
    public function tool_settings_show_the_installed_version_from_the_version_file(): void
    {
        $this->actingAsAdmin();

        $installedVersion = trim((string) file_get_contents(base_path('VERSION')));

        $this->assertSame($installedVersion, config('app.version'));
        $this->get(route('tool.branding'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('artworkVersion', $installedVersion));
    }

    #[Test]
    public function missing_version_is_shared_as_null(): void
    {
        $this->actingAsAdmin();
        config(['app.version' => null]);

        $this->get(route('tool.branding'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('artworkVersion', null));
    }

    #[Test]
    public function the_version_is_not_shared_with_guests(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('artworkVersion', null));
    }
}
