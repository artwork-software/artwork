<?php

namespace Tests\Feature\Http\Controllers;

use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class ToolSettingsCommunicationAndLegalControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_view_communication_and_legal(): void
    {
        $this->get(route('tool.communication-and-legal'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_view_communication_and_legal(): void
    {
        $this->actingAsAdmin();

        $response = $this->get(route('tool.communication-and-legal'));

        $response->assertOk();
    }
}
