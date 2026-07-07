<?php

namespace Tests\Feature\Http\Controllers;

use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class ShiftSettingsControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_view_shift_settings(): void
    {
        $this->get(route('shift.settings'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_view_shift_settings(): void
    {
        $this->actingAsAdmin();

        $response = $this->get(route('shift.settings'));

        $response->assertOk();
    }

    #[Test]
    public function admin_can_update_use_first_name_for_sort(): void
    {
        $this->actingAsAdmin();

        $response = $this->patch(
            route('shift.settings.update.shift-settings.use-first-name-for-sort'),
            ['use_first_name_for_sort' => true]
        );

        $response->assertRedirect();
    }

    #[Test]
    public function admin_can_update_calendar_abo_show_all_shifts(): void
    {
        $this->actingAsAdmin();

        $response = $this->patch(
            route('shift.settings.update.calendar-abo-show-all-shifts'),
            ['calendar_abo_show_all_shifts' => true]
        );

        $response->assertRedirect();
    }
}
