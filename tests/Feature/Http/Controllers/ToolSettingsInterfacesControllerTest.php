<?php

namespace Tests\Feature\Http\Controllers;

use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class ToolSettingsInterfacesControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_view_interfaces(): void
    {
        $this->get(route('tool.interfaces'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_view_interfaces(): void
    {
        $this->actingAsAdmin();

        $response = $this->get(route('tool.interfaces'));

        $response->assertOk();
    }
}
