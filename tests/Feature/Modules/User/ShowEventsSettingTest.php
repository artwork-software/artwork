<?php

namespace Tests\Feature\Modules\User;

use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Anzeigeeinstellung „Termine anzeigen" (user_shift_plan_settings.show_events, Dienstplan-Wochenansicht).
 */
final class ShowEventsSettingTest extends FeatureTestCase
{
    private function patchSettings(User $user, array $payload): void
    {
        $this->patchJson(route('user.calendar_settings.update', $user), $payload)
            ->assertSuccessful();
    }

    #[Test]
    public function defaults_to_visible(): void
    {
        $user = User::factory()->create();
        $user->shift_plan_settings()->create();

        $this->assertTrue($user->fresh()->shift_plan_settings->show_events);
    }

    #[Test]
    public function can_be_hidden_and_shown_again_for_the_shift_plan(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->patchSettings($user, ['is_shift_plan' => true, 'show_events' => false]);
        $this->assertFalse($user->fresh()->shift_plan_settings->show_events);

        $this->patchSettings($user, ['is_shift_plan' => true, 'show_events' => true]);
        $this->assertTrue($user->fresh()->shift_plan_settings->show_events);
    }

    #[Test]
    public function saving_other_settings_keeps_the_value(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $user->shift_plan_settings()->create(['show_events' => false]);

        $this->patchSettings($user, ['is_shift_plan' => true, 'high_contrast' => true]);

        $settings = $user->fresh()->shift_plan_settings;
        $this->assertFalse($settings->show_events);
        $this->assertTrue($settings->high_contrast);
    }
}
