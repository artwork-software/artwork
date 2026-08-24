<?php

namespace Tests\Feature\Modules\User;

use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class UserCalendarShowEventStatusSettingTest extends FeatureTestCase
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
    public function showEventStatusDefaultsToFalse(): void
    {
        $user = $this->makeUserWithCalendarSettings();

        // Default AUS: die zusätzliche Statuszeile in der Termin-Kachel ist opt-in.
        self::assertFalse($user->calendar_settings()->first()->show_event_status);
    }

    #[Test]
    public function showEventStatusCanBeEnabledForCalendarSettings(): void
    {
        $user = $this->makeUserWithCalendarSettings();

        $this->actingAs($user)
            ->patch(route('user.calendar_settings.update', ['user' => $user->id]), [
                'is_daily_view' => false,
                'is_shift_plan' => false,
                'show_event_status' => true,
            ])
            ->assertSessionHasNoErrors();

        self::assertTrue($user->calendar_settings()->first()->show_event_status);
    }

    #[Test]
    public function showEventStatusIsPersistedForDailyViewCalendarSettings(): void
    {
        $user = $this->makeUserWithCalendarSettings();

        $this->actingAs($user)
            ->patch(route('user.calendar_settings.update', ['user' => $user->id]), [
                'is_daily_view' => true,
                'is_shift_plan' => false,
                'show_event_status' => true,
            ])
            ->assertSessionHasNoErrors();

        self::assertTrue($user->daily_view_calendar_settings()->first()->show_event_status);
    }

    #[Test]
    public function showEventStatusIsAcceptedFromShiftPlanScopesWithoutError(): void
    {
        // Das geteilte Settings-Modal sendet das Feld auch aus den Schichtplan-Scopes
        // mit — die Spalte existiert dort, auch wenn der Status nur im Kalender angezeigt wird.
        $user = $this->makeUserWithCalendarSettings();

        $this->actingAs($user)
            ->patch(route('user.calendar_settings.update', ['user' => $user->id]), [
                'is_daily_view' => false,
                'is_shift_plan' => true,
                'show_event_status' => true,
            ])
            ->assertSessionHasNoErrors();

        self::assertTrue($user->shift_plan_settings()->first()->show_event_status);
    }
}
