<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\EventType\Models\EventType;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class EventTypeControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_store_event_type(): void
    {
        $this->post(route('event_types.store'), ['name' => 'Foo'])
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_store_event_type(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('event_types.store'), [
            'name' => 'Concert',
            'hex_code' => '#FF0000',
            'project_mandatory' => false,
            'individual_name' => false,
            'abbreviation' => 'CON',
            'relevant_for_project_period' => false,
            'verification_mode' => 'none',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('event_types', ['name' => 'Concert']);
    }

    #[Test]
    public function admin_can_update_event_type(): void
    {
        $this->actingAsAdmin();
        $eventType = EventType::factory()->create();

        $response = $this->patch(route('event_types.update', $eventType), [
            'name' => 'Updated',
            'users' => [],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('event_types', ['id' => $eventType->id, 'name' => 'Updated']);
    }
}
