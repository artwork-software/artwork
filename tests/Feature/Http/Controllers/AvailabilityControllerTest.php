<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Availability\Models\Availability;
use Artwork\Modules\Availability\Models\AvailabilitySeries;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class AvailabilityControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_update(): void
    {
        $availability = Availability::factory()->create();
        $this->patch(route('update.availability', $availability), [])
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function guest_cannot_destroy(): void
    {
        $availability = Availability::factory()->create();
        $this->delete(route('delete.availability', $availability))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_destroy(): void
    {
        $this->actingAsAdmin();
        $availability = Availability::factory()->create();

        $response = $this->delete(route('delete.availability', $availability));

        $response->assertRedirect();
    }

    #[Test]
    public function admin_can_destroy_series(): void
    {
        $this->actingAsAdmin();
        $series = AvailabilitySeries::factory()->create();

        $response = $this->delete(route('delete.availability.series', $series));

        $response->assertRedirect();
    }
}
