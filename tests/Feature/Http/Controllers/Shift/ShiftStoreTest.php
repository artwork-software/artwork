<?php

namespace Tests\Feature\Http\Controllers\Shift;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Shift\Models\Shift;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class ShiftStoreTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_store_shift_for_event(): void
    {
        $event = Event::factory()->create();

        $this->postJson(route('event.shift.store', $event), [])
            ->assertUnauthorized();
    }

    #[Test]
    public function guest_cannot_store_shift_without_event(): void
    {
        $this->postJson(route('event.shift.store.without.event'), [])
            ->assertUnauthorized();
    }

    #[Test]
    public function guest_cannot_store_shift_multi_add(): void
    {
        $this->postJson(route('event.shift.store.multi.add'), [])
            ->assertUnauthorized();
    }

    #[Test]
    public function guest_cannot_create_timeline_event(): void
    {
        $event = Event::factory()->create();

        $this->postJson(route('create.timeline.event', $event), [])
            ->assertUnauthorized();
    }

    #[Test]
    public function guest_cannot_update_timeline_event(): void
    {
        $event = Event::factory()->create();

        $this->postJson(route('edit.timeline.event', $event), [])
            ->assertUnauthorized();
    }

    #[Test]
    public function guest_cannot_store_timeline_preset_from_event(): void
    {
        $event = Event::factory()->create();

        $this->postJson(route('timeline-preset.store.form.event', $event), [])
            ->assertUnauthorized();
    }

    #[Test]
    public function admin_can_add_timeline_to_event_with_empty_dataset(): void
    {
        $this->actingAsAdmin();
        $event = Event::factory()->create();

        $response = $this->postJson(route('create.timeline.event', $event), ['dataset' => []]);

        $response->assertSuccessful();
    }

    #[Test]
    public function admin_can_update_timeline_with_empty_dataset(): void
    {
        $this->actingAsAdmin();
        $event = Event::factory()->create();

        $response = $this->postJson(route('edit.timeline.event', $event), ['dataset' => []]);

        $response->assertSuccessful();
    }

    #[Test]
    public function add_timeline_event_returns_404_for_unknown_event(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson(route('create.timeline.event', ['event' => PHP_INT_MAX]), ['dataset' => []]);

        $response->assertNotFound();
    }
}
