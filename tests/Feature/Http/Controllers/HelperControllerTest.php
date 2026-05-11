<?php

namespace Tests\Feature\Http\Controllers;

use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class HelperControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_call_calendar_week_helper(): void
    {
        $this->getJson(route('api.helper.calendar-week', [
            'week_number' => 1,
            'year' => 2025,
        ]))->assertUnauthorized();
    }

    #[Test]
    public function admin_gets_date_range_for_valid_week(): void
    {
        $this->actingAsAdmin();

        $response = $this->getJson(route('api.helper.calendar-week', [
            'week_number' => 1,
            'year' => 2025,
        ]));

        $response->assertOk();
        $response->assertJsonStructure(['start_date', 'end_date']);
    }

    #[Test]
    public function admin_gets_400_for_invalid_week(): void
    {
        $this->actingAsAdmin();

        $response = $this->getJson(route('api.helper.calendar-week', [
            'week_number' => 60, // invalid
            'year' => 2025,
        ]));

        $response->assertStatus(400);
        $response->assertJsonStructure(['error']);
    }
}
