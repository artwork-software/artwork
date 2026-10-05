<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
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

    /**
     * Die Settings-Karte „Schichtrelevante Termintypen" ist auskommentiert, der globale Schalter
     * bestimmt aber weiter die Vorauswahl neuer Projekte (ProjectController::store) — die Route
     * bleibt deshalb bestehen.
     */
    #[Test]
    public function general_shift_settings_editor_can_toggle_shift_relevance(): void
    {
        $this->actingAsUserWith([
            PermissionEnum::SHIFT_SETTINGS_VIEW_EDIT,
            PermissionEnum::SHIFT_SETTINGS_GENERAL_EDIT,
        ]);
        $eventType = EventType::factory()->create(['relevant_for_shift' => false]);

        $this->patch(route('event-type.update.relevant', $eventType), ['relevant_for_shift' => true])
            ->assertOk();
        $this->assertTrue($eventType->fresh()->relevant_for_shift);

        $this->patch(route('event-type.update.relevant', $eventType), ['relevant_for_shift' => false])
            ->assertOk();
        $this->assertFalse($eventType->fresh()->relevant_for_shift);
    }

    #[Test]
    public function user_without_shift_settings_permission_cannot_toggle_shift_relevance(): void
    {
        $this->actingAs(User::factory()->create());
        $eventType = EventType::factory()->create(['relevant_for_shift' => false]);

        $this->patch(route('event-type.update.relevant', $eventType), ['relevant_for_shift' => true])
            ->assertForbidden();
        $this->assertFalse($eventType->fresh()->relevant_for_shift);
    }
}
