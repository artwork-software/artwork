<?php

namespace Tests\Feature\Http\Controllers\Shift;

use Artwork\Modules\Shift\Models\Shift;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class ShiftAssignmentTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_assign_to_shift(): void
    {
        $shift = Shift::factory()->create();

        $this->postJson(route('shift.assignUserByType', $shift), [])
            ->assertUnauthorized();
    }

    #[Test]
    public function admin_assign_with_unknown_user_type_fails_validation(): void
    {
        $this->actingAsAdmin();
        $shift = Shift::factory()->create();

        $this->postJson(route('shift.assignUserByType', $shift), [
            'userType' => 99,
            'userId' => 1,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['userType', 'shiftQualificationId']);
    }

    #[Test]
    public function admin_assign_with_no_user_type_fails_validation(): void
    {
        $this->actingAsAdmin();
        $shift = Shift::factory()->create();

        $this->postJson(route('shift.assignUserByType', $shift), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['userId', 'userType', 'shiftQualificationId']);
    }

    #[Test]
    public function assign_to_shift_returns_404_for_unknown_shift(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson(route('shift.assignUserByType', ['shift' => PHP_INT_MAX]), []);

        $response->assertNotFound();
    }

    #[Test]
    public function admin_assign_with_is_shift_tab_returns_redirect(): void
    {
        $this->actingAsAdmin();
        $shift = Shift::factory()->create();

        $response = $this->post(route('shift.assignUserByType', $shift), [
            'isShiftTab' => true,
            'userType' => 99,
        ]);

        $response->assertRedirect();
    }
}
