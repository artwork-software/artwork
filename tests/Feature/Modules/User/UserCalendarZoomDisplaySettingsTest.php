<?php

namespace Tests\Feature\Modules\User;

use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class UserCalendarZoomDisplaySettingsTest extends FeatureTestCase
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
    public function calendarColumnWidthAndArtistNameSettingArePersisted(): void
    {
        $user = $this->makeUserWithCalendarSettings();

        $this->actingAs($user)
            ->patch(route('user.calendar_settings.update', ['user' => $user->id]), [
                'is_daily_view' => false,
                'is_shift_plan' => false,
                'calendar_column_width' => 280,
                'show_artist_names_as_title' => true,
            ])
            ->assertSessionHasNoErrors();

        $settings = $user->calendar_settings()->first();
        self::assertSame(280, $settings->calendar_column_width);
        self::assertTrue($settings->show_artist_names_as_title);
    }

    #[Test]
    public function calendarSettingsHaveSensibleDefaults(): void
    {
        $user = $this->makeUserWithCalendarSettings();

        $settings = $user->calendar_settings()->first();
        self::assertSame(212, $settings->calendar_column_width);
        self::assertFalse($settings->show_artist_names_as_title);
    }

    #[Test]
    public function shiftPlanSettingsSaveIsNotBrokenByCalendarOnlyFields(): void
    {
        $user = $this->makeUserWithCalendarSettings();

        // Das Schichtplan-Modal sendet die kalender-spezifischen Felder nicht;
        // der Save-Pfad darf nicht auf die (dort fehlenden) Spalten schreiben.
        $this->actingAs($user)
            ->patch(route('user.calendar_settings.update', ['user' => $user->id]), [
                'is_daily_view' => false,
                'is_shift_plan' => true,
                'expand_days' => true,
            ])
            ->assertSessionHasNoErrors();

        self::assertTrue((bool) $user->shift_plan_settings()->first()->expand_days);
    }

    #[Test]
    public function zoomFactorIsPersistedViaAxiosStylePatch(): void
    {
        $user = $this->makeUserWithCalendarSettings();

        $this->actingAs($user)
            ->patch(route('user.update.zoom_factor', ['user' => $user->id]), [
                'zoom_factor' => 0.4,
            ])
            ->assertSessionHasNoErrors();

        self::assertSame(0.4, $user->fresh()->zoom_factor);
    }

    #[Test]
    public function shiftPlanZoomFactorIsPersistedAndCreatesSettingsIfMissing(): void
    {
        $user = $this->makeUserWithCalendarSettings();
        self::assertNull($user->shift_plan_settings);

        $this->actingAs($user)
            ->patch(route('user.update.shift_plan_zoom_factor', ['user' => $user->id]), [
                'zoom_factor' => 0.55,
            ])
            ->assertSessionHasNoErrors();

        self::assertSame(0.55, $user->shift_plan_settings()->first()->zoom_factor);
    }

    #[Test]
    public function shiftPlanZoomFactorRejectsOutOfRangeValues(): void
    {
        $user = $this->makeUserWithCalendarSettings();

        $this->actingAs($user)
            ->patch(route('user.update.shift_plan_zoom_factor', ['user' => $user->id]), [
                'zoom_factor' => 3,
            ])
            ->assertSessionHasErrors('zoom_factor');
    }
}
