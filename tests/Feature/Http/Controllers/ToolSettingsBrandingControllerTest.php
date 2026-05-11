<?php

namespace Tests\Feature\Http\Controllers;

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
}
