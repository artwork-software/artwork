<?php

namespace Tests\Feature\Modules\User;

use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Hell/Dunkel-Umschalter des Personenbereichs im Schichtplan
 * (user_shift_plan_settings.user_overview_light_mode).
 */
final class UserOverviewLightModeSettingTest extends FeatureTestCase
{
    private function patchSettings(User $user, array $payload): void
    {
        $this->patchJson(route('user.calendar_settings.update', $user), $payload)
            ->assertSuccessful();
    }

    #[Test]
    public function defaults_to_dark_mode(): void
    {
        $user = User::factory()->create();
        $user->shift_plan_settings()->create();

        $this->assertFalse($user->fresh()->shift_plan_settings->user_overview_light_mode);
    }

    #[Test]
    public function light_mode_can_be_enabled_and_disabled_for_the_shift_plan(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->patchSettings($user, [
            'is_shift_plan' => true,
            'user_overview_light_mode' => true,
        ]);
        $this->assertTrue($user->fresh()->shift_plan_settings->user_overview_light_mode);

        $this->patchSettings($user, [
            'is_shift_plan' => true,
            'user_overview_light_mode' => false,
        ]);
        $this->assertFalse($user->fresh()->shift_plan_settings->user_overview_light_mode);
    }

    #[Test]
    public function toggle_does_not_touch_other_shift_plan_settings(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $user->shift_plan_settings()->create(['high_contrast' => true, 'show_user_overview' => false]);

        $this->patchSettings($user, [
            'is_shift_plan' => true,
            'user_overview_light_mode' => true,
        ]);

        $settings = $user->fresh()->shift_plan_settings;
        $this->assertTrue($settings->user_overview_light_mode);
        $this->assertTrue($settings->high_contrast);
        $this->assertFalse($settings->show_user_overview);
    }

    #[Test]
    public function other_users_cannot_change_the_setting(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($other);

        $this->patchJson(route('user.calendar_settings.update', $owner), [
            'is_shift_plan' => true,
            'user_overview_light_mode' => true,
        ])->assertForbidden();
    }
}
