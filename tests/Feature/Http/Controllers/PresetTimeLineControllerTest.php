<?php

namespace Tests\Feature\Http\Controllers;

use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class PresetTimeLineControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_update(): void
    {
        $this->patch(route('preset.timeline.update'), ['timelines' => []])
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_update_with_empty_timelines(): void
    {
        $this->actingAsAdmin();

        $response = $this->patch(route('preset.timeline.update'), ['timelines' => []]);

        $response->assertOk();
    }
}
