<?php

namespace Tests\Feature\Http\Controllers;

use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class EventVerificationControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_view_event_verifications_index(): void
    {
        $this->get(route('event-verifications.index'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_view_event_verifications_index(): void
    {
        $this->actingAsAdmin();

        $response = $this->get(route('event-verifications.index'));

        $response->assertOk();
    }

    #[Test]
    public function admin_can_view_event_verifications_requests(): void
    {
        $this->actingAsAdmin();

        $response = $this->get(route('event-verifications.requests'));

        $response->assertOk();
    }
}
