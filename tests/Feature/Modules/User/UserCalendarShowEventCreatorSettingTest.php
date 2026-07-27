<?php

namespace Tests\Feature\Modules\User;

use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class UserCalendarShowEventCreatorSettingTest extends FeatureTestCase
{
    private function makeUserWithCalendarSettings(): User
    {
        $user = User::factory()->create();
        if ($user->calendar_settings === null) {
            $user->calendar_settings()->create();
        }

        return $user;
    }

    #[Test]
    public function showEventCreatorDefaultsToFalse(): void
    {
        $user = $this->makeUserWithCalendarSettings();

        self::assertFalse($user->calendar_settings()->first()->show_event_creator);
    }

    #[Test]
    public function showEventCreatorIsPersistedForCalendarSettings(): void
    {
        $user = $this->makeUserWithCalendarSettings();

        $this->actingAs($user)
            ->patch(route('user.calendar_settings.update', ['user' => $user->id]), [
                'is_daily_view' => false,
                'is_shift_plan' => false,
                'show_event_creator' => true,
            ])
            ->assertSessionHasNoErrors();

        self::assertTrue($user->calendar_settings()->first()->show_event_creator);
    }

    #[Test]
    public function showEventCreatorIsPersistedForShiftPlanSettings(): void
    {
        $user = $this->makeUserWithCalendarSettings();

        $this->actingAs($user)
            ->patch(route('user.calendar_settings.update', ['user' => $user->id]), [
                'is_daily_view' => false,
                'is_shift_plan' => true,
                'show_event_creator' => true,
            ])
            ->assertSessionHasNoErrors();

        self::assertTrue($user->shift_plan_settings()->first()->show_event_creator);
    }

    #[Test]
    public function showEventCreatorIsPersistedForDailyViewScopes(): void
    {
        $user = $this->makeUserWithCalendarSettings();

        $this->actingAs($user)
            ->patch(route('user.calendar_settings.update', ['user' => $user->id]), [
                'is_daily_view' => true,
                'is_shift_plan' => false,
                'show_event_creator' => true,
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($user)
            ->patch(route('user.calendar_settings.update', ['user' => $user->id]), [
                'is_daily_view' => true,
                'is_shift_plan' => true,
                'show_event_creator' => true,
            ])
            ->assertSessionHasNoErrors();

        self::assertTrue($user->daily_view_calendar_settings()->first()->show_event_creator);
        self::assertTrue($user->shift_plan_daily_settings()->first()->show_event_creator);
    }
}
