<?php

namespace Tests\Feature\Http\Controllers\Event;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class EventUpdateTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_update_event(): void
    {
        $event = Event::factory()->create();

        $this->putJson(route('events.update', $event), [])
            ->assertUnauthorized();
    }

    #[Test]
    public function guest_cannot_update_event_description(): void
    {
        $event = Event::factory()->create();

        $this->patchJson(route('event.update.description', $event), ['description' => 'foo'])
            ->assertUnauthorized();
    }

    #[Test]
    public function admin_can_update_event_description(): void
    {
        $this->actingAsAdmin();
        $event = Event::factory()->create();

        $response = $this->patchJson(route('event.update.description', $event), ['description' => 'New description']);

        $response->assertSuccessful();
        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'description' => 'New description',
        ]);
    }

    #[Test]
    public function update_description_returns_404_for_unknown_event(): void
    {
        $this->actingAsAdmin();

        $response = $this->patchJson(route('event.update.description', ['event' => PHP_INT_MAX]), ['description' => 'x']);

        $response->assertNotFound();
    }

    #[Test]
    public function admin_update_event_with_invalid_payload_returns_validation_error(): void
    {
        $this->actingAsAdmin();
        $event = Event::factory()->create();

        $response = $this->putJson(route('events.update', $event), []);

        $response->assertStatus(422);
    }

    #[Test]
    public function guest_cannot_convert_event_to_planning(): void
    {
        $event = Event::factory()->create();

        $this->postJson(route('events.convertToPlanning', $event))
            ->assertUnauthorized();
    }

    #[Test]
    public function admin_can_convert_event_to_planning(): void
    {
        $this->actingAsAdmin();
        $event = Event::factory()->create(['is_planning' => false]);

        $response = $this->post(route('events.convertToPlanning', $event));

        $response->assertRedirect();
        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'is_planning' => true,
        ]);
    }

    #[Test]
    public function admin_can_restore_trashed_event(): void
    {
        $this->actingAsAdmin();
        $event = Event::factory()->create();
        $event->delete();

        $response = $this->patch(route('events.restore', $event->id));

        $response->assertRedirect(route('events.trashed'));
        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'deleted_at' => null,
        ]);
    }

    #[Test]
    public function restore_event_returns_404_for_unknown_event(): void
    {
        $this->actingAsAdmin();

        $response = $this->patch(route('events.restore', PHP_INT_MAX));

        $response->assertNotFound();
    }

    #[Test]
    public function guest_cannot_accept_event(): void
    {
        $event = Event::factory()->create();

        $this->putJson(route('events.accept', $event), [])
            ->assertUnauthorized();
    }

    #[Test]
    public function guest_cannot_decline_event(): void
    {
        $event = Event::factory()->create();

        $this->putJson(route('events.decline', $event), [])
            ->assertUnauthorized();
    }

    #[Test]
    public function guest_cannot_answer_on_event(): void
    {
        $event = Event::factory()->create();

        $this->postJson(route('event.answer', $event), [])
            ->assertUnauthorized();
    }

    #[Test]
    public function guest_cannot_update_series_events(): void
    {
        $event = Event::factory()->create();

        $this->patchJson(route('events.series.update', $event), [])
            ->assertUnauthorized();
    }

    #[Test]
    public function admin_updating_non_series_event_series_is_noop(): void
    {
        $this->actingAsAdmin();
        $event = Event::factory()->create(['is_series' => false]);

        $response = $this->patchJson(route('events.series.update', $event), [
            'value' => 0,
            'calculationType' => 1,
            'type' => 1,
        ]);

        // Method early-returns without response data
        $response->assertSuccessful();
    }
}
