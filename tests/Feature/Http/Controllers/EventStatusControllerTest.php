<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Event\Models\EventStatus;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class EventStatusControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_view_event_status_index(): void
    {
        $this->get(route('event_status.management'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_view_event_status_index(): void
    {
        $this->actingAsAdmin();

        $response = $this->get(route('event_status.management'));

        $response->assertOk();
    }

    #[Test]
    public function admin_can_store_event_status(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('event_status.store'), [
            'name' => 'Approved',
            'color' => '#00FF00',
            'default' => false,
        ]);

        $this->assertContains($response->status(), [200, 302]);
        $this->assertDatabaseHas('event_statuses', ['name' => 'Approved']);
    }

    #[Test]
    public function store_event_status_validates_name(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('event_status.store'), [
            'color' => '#FF0000',
            'default' => false,
        ]);

        $response->assertSessionHasErrors('name');
    }

    #[Test]
    public function admin_can_reorder_event_statuses(): void
    {
        $this->actingAsAdmin();
        $status = EventStatus::factory()->create(['order' => 1]);

        $response = $this->patch(route('event_status.reorder'), [
            'eventStatuses' => [['id' => $status->id, 'order' => 5]],
        ]);

        $this->assertContains($response->status(), [200, 302]);
        $this->assertDatabaseHas('event_statuses', ['id' => $status->id, 'order' => 5]);
    }
}
