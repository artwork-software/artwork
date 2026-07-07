<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Models\SubEvent;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class SubEventsControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_store(): void
    {
        $this->post(route('subEvent.add'), [])
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_store_subevent(): void
    {
        $this->actingAsAdmin();
        $event = Event::factory()->create();
        $eventType = EventType::factory()->create();
        $user = User::factory()->create();

        $response = $this->post(route('subEvent.add'), [
            'event_id' => $event->id,
            'eventName' => 'My SubEvent',
            'description' => 'desc',
            'start_time' => '2026-01-01 10:00:00',
            'end_time' => '2026-01-01 11:00:00',
            'event_type_id' => $eventType->id,
            'user_id' => $user->id,
            'audience' => false,
            'is_loud' => false,
            'allDay' => false,
            'eventProperties' => [],
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('sub_events', ['eventName' => 'My SubEvent']);
    }

    #[Test]
    public function admin_can_update_subevent(): void
    {
        $this->actingAsAdmin();
        $event = Event::factory()->create();
        $eventType = EventType::factory()->create();
        $user = User::factory()->create();
        $subEvent = SubEvent::factory()->create([
            'event_id' => $event->id,
            'eventName' => 'Old',
        ]);

        $response = $this->patch(route('subEvent.update', $subEvent), [
            'eventName' => 'New',
            'description' => 'd',
            'start_time' => '2026-01-01 10:00:00',
            'end_time' => '2026-01-01 11:00:00',
            'event_type_id' => $eventType->id,
            'user_id' => $user->id,
            'audience' => false,
            'is_loud' => false,
            'allDay' => false,
            'eventProperties' => [],
        ]);

        $response->assertOk();
        $this->assertSame('New', $subEvent->fresh()->eventName);
    }

    #[Test]
    public function admin_can_destroy_subevent(): void
    {
        $this->actingAsAdmin();
        $event = Event::factory()->create();
        $subEvent = SubEvent::factory()->create(['event_id' => $event->id]);

        $response = $this->delete(route('subEvent.delete', $subEvent));

        $response->assertOk();
        $this->assertDatabaseMissing('sub_events', ['id' => $subEvent->id]);
    }
}
