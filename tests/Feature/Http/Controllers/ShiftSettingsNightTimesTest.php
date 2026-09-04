<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Block 1a: Nachtarbeitszeitraum (start_night_time/end_night_time) ist über die
 * Schichteinstellungen pflegbar.
 */
final class ShiftSettingsNightTimesTest extends FeatureTestCase
{
    #[Test]
    public function admin_can_update_night_times(): void
    {
        $this->actingAsAdmin();

        $this->patch(route('shift.settings.update.night-times'), [
            'start_night_time' => '23:00',
            'end_night_time' => '06:00',
        ])->assertRedirect();

        $settings = app(GeneralSettings::class);
        $this->assertSame('23:00', $settings->start_night_time);
        $this->assertSame('06:00', $settings->end_night_time);
    }

    #[Test]
    public function invalid_time_format_is_rejected(): void
    {
        $this->actingAsAdmin();

        $this->patchJson(route('shift.settings.update.night-times'), [
            'start_night_time' => '25:99',
            'end_night_time' => 'abc',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['start_night_time', 'end_night_time']);
    }

    #[Test]
    public function identical_start_and_end_are_rejected(): void
    {
        $this->actingAsAdmin();

        $this->patchJson(route('shift.settings.update.night-times'), [
            'start_night_time' => '22:00',
            'end_night_time' => '22:00',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['end_night_time']);
    }

    #[Test]
    public function user_without_shift_settings_permission_is_forbidden(): void
    {
        $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);

        $this->patchJson(route('shift.settings.update.night-times'), [
            'start_night_time' => '23:00',
            'end_night_time' => '06:00',
        ])->assertForbidden();
    }

    #[Test]
    public function index_passes_night_times_prop(): void
    {
        $this->actingAsAdmin();

        $this->get(route('shift.settings'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('nightTimes.start_night_time')
                ->has('nightTimes.end_night_time'));
    }
}
