<?php

namespace Tests\Feature\Modules\User;

use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserCalendarSettings;
use Artwork\Modules\User\Models\UserDailyViewCalendarSettings;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Kalender und Planungskalender haben je eine eigene Einstellungszeile (calendar_scope). Einstellungen, die
 * global gemeint sind, müssen beide Zeilen treffen (Prod-Review 20.10.2026).
 */
final class CalendarScopeSettingsFollowUpTest extends FeatureTestCase
{
    private function userWithBothScopes(array $attributes): User
    {
        $user = User::factory()->create();
        $user->calendar_settings()->updateOrCreate([], $attributes);
        $user->planningCalendarSettings()->updateOrCreate([], $attributes);
        $user->daily_view_calendar_settings()->updateOrCreate([], $attributes);
        $user->planningDailyViewCalendarSettings()->updateOrCreate([], $attributes);

        return $user;
    }

    #[Test]
    public function disabling_event_statuses_turns_off_the_status_colour_in_every_calendar(): void
    {
        $user = $this->userWithBothScopes(['use_event_status_color' => true]);
        $this->actingAsAdmin();

        $this->patch(route('event_status.update_settings'), ['enable_status' => false])->assertSuccessful();

        $this->assertSame(0, UserCalendarSettings::query()
            ->where('user_id', $user->id)->where('use_event_status_color', true)->count());
        $this->assertSame(0, UserDailyViewCalendarSettings::query()
            ->where('user_id', $user->id)->where('use_event_status_color', true)->count());
    }

    #[Test]
    public function high_contrast_from_the_profile_applies_to_calendar_and_planning_calendar(): void
    {
        $user = $this->userWithBothScopes(['high_contrast' => false]);
        $this->actingAs($user);

        $this->patch(route('user.update', $user), [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'high_contrast' => true,
        ])->assertRedirect();

        $this->assertTrue($user->calendar_settings()->first()->high_contrast);
        $this->assertTrue($user->planningCalendarSettings()->first()->high_contrast);
    }
}
