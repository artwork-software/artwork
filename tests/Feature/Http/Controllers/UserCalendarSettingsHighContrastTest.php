<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class UserCalendarSettingsHighContrastTest extends FeatureTestCase
{
    private function patchSettings(User $user, array $payload): void
    {
        $this->patchJson(route('user.calendar_settings.update', $user), $payload)
            ->assertSuccessful();
    }

    #[Test]
    public function shift_plan_high_contrast_can_be_enabled_and_disabled(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->patchSettings($user, [
            'is_shift_plan' => true,
            'is_daily_view' => false,
            'high_contrast' => true,
        ]);
        $this->assertTrue($user->fresh()->shift_plan_settings->high_contrast);

        $this->patchSettings($user, [
            'is_shift_plan' => true,
            'is_daily_view' => false,
            'high_contrast' => false,
        ]);
        $this->assertFalse($user->fresh()->shift_plan_settings->high_contrast);
    }

    #[Test]
    public function shift_plan_daily_high_contrast_can_be_enabled_and_disabled(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->patchSettings($user, [
            'is_shift_plan' => true,
            'is_daily_view' => true,
            'high_contrast' => true,
        ]);
        $this->assertTrue($user->fresh()->shift_plan_daily_settings->high_contrast);

        $this->patchSettings($user, [
            'is_shift_plan' => true,
            'is_daily_view' => true,
            'high_contrast' => false,
        ]);
        $this->assertFalse($user->fresh()->shift_plan_daily_settings->high_contrast);
    }

    #[Test]
    public function calendar_high_contrast_can_be_enabled_and_disabled(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->patchSettings($user, [
            'is_shift_plan' => false,
            'is_daily_view' => false,
            'high_contrast' => true,
        ]);
        $this->assertTrue($user->fresh()->calendar_settings->high_contrast);

        $this->patchSettings($user, [
            'is_shift_plan' => false,
            'is_daily_view' => false,
            'high_contrast' => false,
        ]);
        $this->assertFalse($user->fresh()->calendar_settings->high_contrast);
    }
}
