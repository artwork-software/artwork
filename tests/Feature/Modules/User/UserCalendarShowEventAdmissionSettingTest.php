<?php

namespace Tests\Feature\Modules\User;

use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class UserCalendarShowEventAdmissionSettingTest extends FeatureTestCase
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
    public function showEventAdmissionDefaultsToTrue(): void
    {
        $user = $this->makeUserWithCalendarSettings();

        // Default AN: Ist das Einlass-Feld instanzweit aktiv, soll es ohne
        // weiteres Zutun sichtbar sein (Nutzer können es abwählen).
        self::assertTrue($user->calendar_settings()->first()->show_event_admission);
    }

    #[Test]
    public function showEventAdmissionCanBeDisabledForCalendarSettings(): void
    {
        $user = $this->makeUserWithCalendarSettings();

        $this->actingAs($user)
            ->patch(route('user.calendar_settings.update', ['user' => $user->id]), [
                'is_daily_view' => false,
                'is_shift_plan' => false,
                'show_event_admission' => false,
            ])
            ->assertSessionHasNoErrors();

        self::assertFalse($user->calendar_settings()->first()->show_event_admission);
    }

    #[Test]
    public function showEventAdmissionIsPersistedForShiftPlanSettings(): void
    {
        $user = $this->makeUserWithCalendarSettings();

        $this->actingAs($user)
            ->patch(route('user.calendar_settings.update', ['user' => $user->id]), [
                'is_daily_view' => false,
                'is_shift_plan' => true,
                'show_event_admission' => false,
            ])
            ->assertSessionHasNoErrors();

        self::assertFalse($user->shift_plan_settings()->first()->show_event_admission);
    }

    #[Test]
    public function showEventAdmissionIsPersistedForDailyViewScopes(): void
    {
        $user = $this->makeUserWithCalendarSettings();

        $this->actingAs($user)
            ->patch(route('user.calendar_settings.update', ['user' => $user->id]), [
                'is_daily_view' => true,
                'is_shift_plan' => false,
                'show_event_admission' => false,
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($user)
            ->patch(route('user.calendar_settings.update', ['user' => $user->id]), [
                'is_daily_view' => true,
                'is_shift_plan' => true,
                'show_event_admission' => false,
            ])
            ->assertSessionHasNoErrors();

        self::assertFalse($user->daily_view_calendar_settings()->first()->show_event_admission);
        self::assertFalse($user->shift_plan_daily_settings()->first()->show_event_admission);
    }
}
