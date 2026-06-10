<?php

namespace Tests\Feature\Http\Controllers\Event;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Project\Models\Project;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class EventIndexTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_access_dashboard(): void
    {
        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function guest_cannot_access_calendar_view(): void
    {
        $this->get(route('events'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function guest_cannot_access_event_requests_index(): void
    {
        $this->get(route('events.requests'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function guest_cannot_access_trashed_events(): void
    {
        $this->get(route('events.trashed'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function guest_cannot_access_planning_calendar(): void
    {
        $this->get(route('planning-event-calendar.index'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function guest_cannot_access_events_all_api(): void
    {
        $response = $this->getJson(route('events.all'));

        $this->assertContains($response->status(), [302, 401, 403]);
    }

    #[Test]
    public function admin_can_view_trashed_events(): void
    {
        $this->actingAsAdmin();

        $response = $this->get(route('events.trashed'));

        $response->assertOk();
    }

    #[Test]
    public function admin_sees_trashed_events_only(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();
        $trashed = Event::factory()->create(['project_id' => $project->id]);
        $trashed->delete();
        $active = Event::factory()->create(['project_id' => $project->id]);

        $response = $this->get(route('events.trashed'));

        $response->assertOk();
        $this->assertSoftDeleted('events', ['id' => $trashed->id]);
        $this->assertDatabaseHas('events', ['id' => $active->id, 'deleted_at' => null]);
    }

    #[Test]
    public function event_requests_index_redirects_to_event_verifications(): void
    {
        $this->actingAsAdmin();

        $response = $this->get(route('events.requests'));

        // The legacy event requests page was replaced by event verifications.
        $response->assertRedirect(route('event-verifications.index'));
    }

    #[Test]
    public function admin_can_get_timelines_for_event(): void
    {
        $this->actingAsAdmin();
        $event = Event::factory()->create();

        $response = $this->getJson(route('events.timelines', $event));

        $response->assertOk();
    }

    #[Test]
    public function get_timelines_returns_404_for_unknown_event(): void
    {
        $this->actingAsAdmin();

        $response = $this->getJson(route('events.timelines', ['event' => PHP_INT_MAX]));

        $response->assertNotFound();
    }
}
