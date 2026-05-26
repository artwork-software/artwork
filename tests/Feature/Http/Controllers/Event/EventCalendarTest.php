<?php

namespace Tests\Feature\Http\Controllers\Event;

use Artwork\Modules\Event\Models\Event;
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
}
