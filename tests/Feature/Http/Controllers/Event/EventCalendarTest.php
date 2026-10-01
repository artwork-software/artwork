<?php

namespace Tests\Feature\Http\Controllers\Event;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\User\Enums\UserFilterTypes;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class EventCalendarTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_redirect_to_calendar_for_event(): void
    {
        $event = Event::factory()->create();

        $this->get(route('dashboard.redirect-to-calendar', $event))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function guest_cannot_redirect_calendar_by_day(): void
    {
        $this->get(route('calendar.redirect-by-day', ['day' => '2026-01-01']))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function guest_cannot_access_room_events(): void
    {
        $response = $this->getJson(route('events.for-rooms-by-days-and-project'));

        $this->assertContains($response->status(), [302, 401, 403]);
    }

    #[Test]
    public function admin_room_events_returns_empty_when_no_rooms_or_days(): void
    {
        $this->actingAsAdmin();

        $response = $this->getJson(route('events.for-rooms-by-days-and-project'));

        $response->assertOk();
        $response->assertJson([
            'roomData' => [],
            'eventsWithoutRoom' => [],
        ]);
    }

    #[Test]
    public function guest_cannot_access_shift_plan_meta_api(): void
    {
        $response = $this->getJson(route('shift.plan.meta'));

        $this->assertContains($response->status(), [302, 401, 403]);
    }

    #[Test]
    public function admin_can_access_shift_plan_meta_api(): void
    {
        $this->actingAsAdmin();

        $response = $this->getJson(route('shift.plan.meta'));

        $response->assertOk();
    }

    #[Test]
    public function shift_plan_room_api_requires_room_id(): void
    {
        $this->actingAsAdmin();

        $response = $this->getJson(route('shift.plan.room'));

        $response->assertStatus(422);
        $response->assertJsonStructure(['error']);
    }

    #[Test]
    public function shift_plan_room_api_returns_404_for_unknown_room(): void
    {
        $this->actingAsAdmin();

        $response = $this->getJson(route('shift.plan.room', ['room_id' => PHP_INT_MAX]));

        $this->assertContains($response->status(), [404, 200, 422]);
    }

    #[Test]
    public function guest_cannot_access_events_for_rooms_with_user(): void
    {
        $response = $this->get(route('shifts.events.for-rooms-by-days-and-project'));

        $this->assertContains($response->status(), [302, 401, 403]);
    }

    #[Test]
    public function guest_cannot_access_events_for_rooms_no_workers(): void
    {
        $response = $this->get(route('shifts.events.for-rooms-by-days-and-project-no-workers'));

        $this->assertContains($response->status(), [302, 401, 403]);
    }

    #[Test]
    public function redirect_to_event_moves_the_week_view_to_the_event_week(): void
    {
        $user = $this->actingAsAdmin();
        $event = Event::factory()->create(['start_time' => '2026-11-11 19:00:00', 'end_time' => '2026-11-11 21:00:00']);

        $this->get(route('dashboard.redirect-to-calendar', $event))->assertRedirect();

        $this->assertFilterDates($user, UserFilterTypes::CALENDAR_FILTER, '2026-11-09', '2026-11-15');
    }

    #[Test]
    public function redirect_to_event_moves_the_daily_view_when_it_is_active(): void
    {
        $user = $this->actingAsAdmin();
        $user->forceFill(['calendar_daily_view' => true])->save();
        $event = Event::factory()->create(['start_time' => '2026-11-11 19:00:00', 'end_time' => '2026-11-11 21:00:00']);

        $this->get(route('event-verifications.redirect-to-calendar', $event))->assertRedirect();

        $this->assertFilterDates($user, UserFilterTypes::CALENDAR_DAILY_FILTER, '2026-11-09', '2026-11-15');
    }

    #[Test]
    public function redirect_by_day_works_without_existing_calendar_filter(): void
    {
        $user = $this->actingAsAdmin();
        $user->userFilters()->delete();

        $this->get(route('calendar.redirect-by-day', ['day' => '2026-03-02']))->assertRedirect(route('events'));

        $this->assertFilterDates($user, UserFilterTypes::CALENDAR_FILTER, '2026-03-02', '2026-03-09');
    }

    #[Test]
    public function redirect_by_day_updates_all_views_when_the_period_is_shared(): void
    {
        $user = $this->actingAsAdmin();
        $user->forceFill(['share_calendar_date' => true])->save();

        $this->get(route('calendar.redirect-by-day', ['day' => '2026-03-02']))->assertRedirect(route('events'));

        $this->assertFilterDates($user, UserFilterTypes::SHIFT_FILTER, '2026-03-02', '2026-03-09');
    }

    private function assertFilterDates(User $user, UserFilterTypes $type, string $start, string $end): void
    {
        $filter = $user->userFilters()->where('filter_type', $type->value)->first();

        $this->assertNotNull($filter, $type->value . ' fehlt');
        $this->assertSame($start, $filter->start_date->format('Y-m-d'));
        $this->assertSame($end, $filter->end_date->format('Y-m-d'));
    }
}
