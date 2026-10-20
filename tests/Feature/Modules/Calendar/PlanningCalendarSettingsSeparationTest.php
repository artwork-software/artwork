<?php

namespace Tests\Feature\Modules\Calendar;

use Artwork\Modules\Calendar\Services\CalendarDataService;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\User\Models\UserCalendarSettings;
use Artwork\Modules\User\Models\UserDailyViewCalendarSettings;
use Carbon\Carbon;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Kalender und Planungskalender haben getrennte Anzeigeeinstellungen: „Geplante Termine anzeigen“ im Kalender
 * blendete vorher auch im Planungskalender Räume samt geplanter Termine aus.
 */
final class PlanningCalendarSettingsSeparationTest extends FeatureTestCase
{
    #[Test]
    public function settings_saved_in_the_planning_calendar_do_not_change_the_calendar(): void
    {
        $admin = $this->actingAsAdmin();
        $admin->calendarSettingsFor(false, false)->update(['high_contrast' => false]);

        $this->patch(route('user.calendar_settings.update', $admin), [
            'is_planning' => true,
            'high_contrast' => true,
        ])->assertSuccessful();

        $this->assertTrue((bool) $admin->planningCalendarSettings()->first()->high_contrast);
        $this->assertFalse((bool) $admin->calendar_settings()->first()->high_contrast);
    }

    #[Test]
    public function settings_saved_in_the_calendar_do_not_change_the_planning_calendar(): void
    {
        $admin = $this->actingAsAdmin();
        $admin->calendarSettingsFor(true, false)->update(['show_planned_events' => true]);

        $this->patch(route('user.calendar_settings.update', $admin), [
            'show_planned_events' => false,
        ])->assertSuccessful();

        $this->assertFalse((bool) $admin->calendar_settings()->first()->show_planned_events);
        $this->assertTrue((bool) $admin->planningCalendarSettings()->first()->show_planned_events);
    }

    #[Test]
    public function the_daily_view_is_separated_as_well(): void
    {
        $admin = $this->actingAsAdmin();

        $this->patch(route('user.calendar_settings.update', $admin), [
            'is_planning' => true,
            'is_daily_view' => true,
            'expand_days' => true,
        ])->assertSuccessful();

        $planningDaily = $admin->planningDailyViewCalendarSettings()->first();
        $this->assertSame(UserDailyViewCalendarSettings::SCOPE_PLANNING, $planningDaily->calendar_scope);
        $this->assertTrue((bool) $planningDaily->expand_days);
        $this->assertFalse((bool) $admin->calendarSettingsFor(false, true)->expand_days);
    }

    #[Test]
    public function the_planning_calendar_page_shares_its_own_settings(): void
    {
        $admin = $this->actingAsAdmin();
        $admin->calendarSettingsFor(false, false)->update(['show_unplanned_events' => false, 'high_contrast' => false]);
        $admin->calendarSettingsFor(true, false)->update(['show_unplanned_events' => true, 'high_contrast' => true]);

        $this->get(route('planning-event-calendar.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('auth.user.calendar_settings.calendar_scope', UserCalendarSettings::SCOPE_PLANNING)
                ->where('auth.user.calendar_settings.high_contrast', true)
                ->where('calendar_settings.calendar_scope', UserCalendarSettings::SCOPE_PLANNING)
                ->missing('auth.user.planning_calendar_settings')
                ->etc());
    }

    #[Test]
    public function the_calendar_page_keeps_sharing_the_calendar_settings(): void
    {
        $admin = $this->actingAsAdmin();
        $admin->calendarSettingsFor(true, false)->update(['high_contrast' => true]);
        $admin->calendarSettingsFor(false, false)->update(['high_contrast' => false]);

        $this->get(route('events'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('auth.user.calendar_settings.calendar_scope', UserCalendarSettings::SCOPE_CALENDAR)
                ->where('auth.user.calendar_settings.high_contrast', false)
                ->etc());
    }

    #[Test]
    public function hiding_empty_rooms_in_the_planning_calendar_keeps_rooms_with_planned_events(): void
    {
        $admin = $this->actingAsAdmin();
        $roomWithPlannedEvent = Room::factory()->create(['relevant_for_disposition' => true]);
        $roomWithFixedEvent = Room::factory()->create(['relevant_for_disposition' => true]);
        Event::factory()->create([
            'room_id' => $roomWithPlannedEvent->id,
            'is_planning' => true,
            'start_time' => '2031-03-10 10:00:00',
            'end_time' => '2031-03-10 12:00:00',
        ]);
        Event::factory()->create([
            'room_id' => $roomWithFixedEvent->id,
            'is_planning' => false,
            'start_time' => '2031-03-10 10:00:00',
            'end_time' => '2031-03-10 12:00:00',
        ]);
        // Kalender: geplante Termine ausgeblendet – darf den Planungskalender nicht mehr beeinflussen
        $admin->calendarSettingsFor(false, false)->update(['show_planned_events' => false, 'hide_unoccupied_rooms' => true]);
        $planningSettings = $admin->calendarSettingsFor(true, false);
        $planningSettings->update(['show_unplanned_events' => false, 'hide_unoccupied_rooms' => true]);

        $roomIds = app(CalendarDataService::class)->getFilteredRooms(
            null,
            $planningSettings->fresh(),
            Carbon::parse('2031-03-09'),
            Carbon::parse('2031-03-11'),
            isPlanningCalendar: true
        )->pluck('id');

        $this->assertContains($roomWithPlannedEvent->id, $roomIds);
        $this->assertNotContains($roomWithFixedEvent->id, $roomIds);
    }

    #[Test]
    public function the_planning_calendar_lists_fixed_events_without_room_only_with_show_fixed_events(): void
    {
        $admin = $this->actingAsAdmin();
        $planned = Event::factory()->create(['room_id' => null, 'is_planning' => true]);
        $fixed = Event::factory()->create(['room_id' => null, 'is_planning' => false]);
        $admin->calendarSettingsFor(true, false)->update(['show_unplanned_events' => false]);

        $this->get(route('planning-event-calendar.index'))
            ->assertOk()
            ->assertInertia(function (AssertableInertia $page) use ($planned, $fixed): void {
                $ids = collect($page->toArray()['props']['eventsWithoutRoom'])->pluck('id');
                $this->assertContains($planned->id, $ids);
                $this->assertNotContains($fixed->id, $ids);
            });
    }

    #[Test]
    public function the_project_period_of_the_planning_calendar_is_stored_separately(): void
    {
        $admin = $this->actingAsAdmin();

        $this->patch(route('user.calendar_settings.toggle_calendar_settings_use_project_period'), [
            'use_project_time_period' => false,
            'project_id' => 0,
            'is_planning' => true,
            'is_axios' => true,
        ])->assertOk();

        $this->assertSame(1, UserCalendarSettings::query()
            ->where('user_id', $admin->id)
            ->where('calendar_scope', UserCalendarSettings::SCOPE_PLANNING)
            ->count());
    }
}
