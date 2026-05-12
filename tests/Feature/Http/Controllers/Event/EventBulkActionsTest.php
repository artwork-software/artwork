<?php

namespace Tests\Feature\Http\Controllers\Event;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Project\Models\Project;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class EventBulkActionsTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_bulk_delete_events(): void
    {
        $this->deleteJson(route('event.bulk.multi-edit.delete'), [])
            ->assertUnauthorized();
    }

    #[Test]
    public function admin_can_bulk_delete_events(): void
    {
        $this->actingAsAdmin();
        $eventA = Event::factory()->create();
        $eventB = Event::factory()->create();

        $response = $this->deleteJson(route('event.bulk.multi-edit.delete'), [
            'eventIds' => [$eventA->id, $eventB->id],
        ]);

        $response->assertSuccessful();
        $this->assertSoftDeleted('events', ['id' => $eventA->id]);
        $this->assertSoftDeleted('events', ['id' => $eventB->id]);
    }

    #[Test]
    public function admin_bulk_delete_with_empty_payload_is_noop(): void
    {
        $this->actingAsAdmin();
        $event = Event::factory()->create();

        $response = $this->deleteJson(route('event.bulk.multi-edit.delete'), [
            'eventIds' => [],
        ]);

        $response->assertSuccessful();
        $this->assertDatabaseHas('events', ['id' => $event->id, 'deleted_at' => null]);
    }

    #[Test]
    public function guest_cannot_bulk_multi_edit_events(): void
    {
        $this->postJson(route('events.bulk-multi-edit'), [])
            ->assertUnauthorized();
    }

    #[Test]
    public function admin_can_bulk_multi_edit_events_with_empty_payload(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson(route('events.bulk-multi-edit'), ['eventIds' => []]);

        $response->assertSuccessful();
    }

    #[Test]
    public function guest_cannot_save_multi_edit(): void
    {
        $this->patchJson(route('multi-edit.save'), [])
            ->assertUnauthorized();
    }

    #[Test]
    public function guest_cannot_post_multi_edit_delete(): void
    {
        $this->postJson(route('multi-edit.delete'), [])
            ->assertUnauthorized();
    }

    #[Test]
    public function admin_can_post_multi_edit_delete_with_empty_events_list(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson(route('multi-edit.delete'), ['events' => []]);

        $response->assertSuccessful();
    }

    #[Test]
    public function admin_can_post_multi_edit_delete_with_event_ids(): void
    {
        $this->actingAsAdmin();
        $event = Event::factory()->create();

        $response = $this->postJson(route('multi-edit.delete'), [
            'events' => [$event->id],
        ]);

        $response->assertSuccessful();
        $this->assertSoftDeleted('events', ['id' => $event->id]);
    }

    #[Test]
    public function guest_cannot_save_multi_duplicate(): void
    {
        $this->patchJson(route('multi-duplicate.save'), [])
            ->assertUnauthorized();
    }

    #[Test]
    public function guest_cannot_bulk_project_event_store(): void
    {
        $project = Project::factory()->create();

        $this->postJson(route('events.bulk.store', $project), [])
            ->assertUnauthorized();
    }

    #[Test]
    public function admin_bulk_project_event_store_with_empty_events_redirects(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();

        $response = $this->post(route('events.bulk.store', $project), ['events' => []]);

        $response->assertRedirect();
    }

    #[Test]
    public function guest_cannot_update_single_bulk_event(): void
    {
        $event = Event::factory()->create();

        $this->patchJson(route('event.update.single.bulk', $event), [])
            ->assertUnauthorized();
    }

    #[Test]
    public function guest_cannot_create_single_bulk_event(): void
    {
        $project = Project::factory()->create();

        $this->postJson(route('event.store.bulk.single', $project), [])
            ->assertUnauthorized();
    }

    #[Test]
    public function guest_cannot_view_standard_event_values(): void
    {
        $this->get(route('event.standard.values'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_view_standard_event_values(): void
    {
        $this->actingAsAdmin();

        $response = $this->get(route('event.standard.values'));

        $response->assertOk();
    }

    #[Test]
    public function guest_cannot_save_standard_event_values(): void
    {
        $this->patchJson(route('event.standard.values.update'), [])
            ->assertUnauthorized();
    }

    #[Test]
    public function admin_can_save_standard_event_values(): void
    {
        $this->actingAsAdmin();

        $response = $this->patchJson(route('event.standard.values.update'), [
            'event_time_length_minutes' => 60,
        ]);

        $response->assertSuccessful();
    }

    #[Test]
    public function guest_cannot_commit_shifts(): void
    {
        $this->postJson(route('shifts.commit'), [])
            ->assertUnauthorized();
    }

    #[Test]
    public function guest_cannot_get_shift_plan_crafts(): void
    {
        $response = $this->getJson(route('shifts.crafts'));

        $this->assertContains($response->status(), [302, 401, 403]);
    }

    #[Test]
    public function admin_can_get_shift_plan_crafts(): void
    {
        $this->actingAsAdmin();

        $response = $this->getJson(route('shifts.crafts'));

        $response->assertOk();
        $response->assertJsonStructure(['crafts']);
    }

    #[Test]
    public function guest_cannot_delete_old_notifications(): void
    {
        $this->postJson('/notifications/abc-key', [])
            ->assertUnauthorized();
    }
}
