<?php

namespace Tests\Feature\Http\Controllers\Shift;

use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class ShiftIndexTest extends FeatureTestCase
{
    #[Test]
    public function check_collisions_requires_authentication(): void
    {
        // Liefert Zuweisungs-/Zeitdaten beliebiger Worker und ist daher auth-pflichtig
        $this->postJson(route('shift.check-collisions'), [])
            ->assertUnauthorized();
    }

    #[Test]
    public function admin_check_collisions_without_required_params_returns_400(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson(route('shift.check-collisions'), []);

        $response->assertStatus(400);
        $response->assertJsonStructure(['error']);
    }

    #[Test]
    public function admin_check_collisions_with_empty_people_and_valid_dates_returns_empty(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson(route('shift.check-collisions'), [
            'people' => [],
            'start_date' => '2026-05-10',
            'end_date' => '2026-05-10',
            'start' => '10:00',
            'end' => '12:00',
        ]);

        $response->assertOk();
        $response->assertExactJson([]);
    }

    #[Test]
    public function admin_check_collisions_with_invalid_date_returns_400(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson(route('shift.check-collisions'), [
            'people' => [],
            'start_date' => 'invalid',
            'end_date' => 'still-invalid',
            'start' => '10:00',
            'end' => '12:00',
        ]);

        $response->assertStatus(400);
        $response->assertJsonStructure(['error']);
    }

    #[Test]
    public function guest_cannot_create_shifts_from_presets(): void
    {
        $this->postJson(route('shifts.createFromPresets'), [])
            ->assertUnauthorized();
    }
}
