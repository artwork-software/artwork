<?php

namespace Tests\Feature\Http\Controllers\Shift;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Shift\Models\Shift;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class ShiftUpdateTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_update_shift(): void
    {
        $shift = Shift::factory()->create();

        $this->patchJson(route('event.shift.update', $shift), [])
            ->assertUnauthorized();
    }

    #[Test]
    public function guest_cannot_update_shift_time(): void
    {
        $shift = Shift::factory()->create();

        $this->patchJson(route('event.shift.update.updateTime', $shift), [])
            ->assertUnauthorized();
    }

    #[Test]
    public function guest_cannot_update_shift_description(): void
    {
        $shift = Shift::factory()->create();

        $this->patchJson(route('event.shift.update.updateDescription', $shift), [])
            ->assertUnauthorized();
    }

    #[Test]
    public function admin_can_update_shift_description(): void
    {
        $this->actingAsAdmin();
        $shift = Shift::factory()->create();

        $response = $this->patchJson(route('event.shift.update.updateDescription', $shift), [
            'description' => 'Updated shift description',
        ]);

        $response->assertSuccessful();
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('shifts', [
            'id' => $shift->id,
            'description' => 'Updated shift description',
        ]);
    }

    #[Test]
    public function update_shift_description_returns_404_for_unknown_shift(): void
    {
        $this->actingAsAdmin();

        $response = $this->patchJson(
            route('event.shift.update.updateDescription', ['shift' => PHP_INT_MAX]),
            ['description' => 'x']
        );

        $response->assertNotFound();
    }

    #[Test]
    public function guest_cannot_update_commitments(): void
    {
        $this->patchJson(route('update.shift.commitment'), [])
            ->assertUnauthorized();
    }

    #[Test]
    public function guest_cannot_update_short_description(): void
    {
        $this->postJson(route('shifts.updateShortDescription'), [])
            ->assertUnauthorized();
    }

    #[Test]
    public function admin_update_short_description_validates_required_fields(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson(route('shifts.updateShortDescription'), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['shiftPivotId', 'entity']);
    }

    #[Test]
    public function admin_update_short_description_validates_entity_type(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson(route('shifts.updateShortDescription'), [
            'shiftPivotId' => 1,
            'entity' => ['type' => 'invalid_type'],
            'short_description' => 'foo',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['entity.type']);
    }

    #[Test]
    public function guest_cannot_update_individual_shift_time(): void
    {
        $this->postJson(route('shifts.updateIndividualShiftTime'), [])
            ->assertUnauthorized();
    }

    #[Test]
    public function admin_update_individual_shift_time_returns_404_for_unknown_pivot(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson(route('shifts.updateIndividualShiftTime'), [
            'shiftPivotId' => PHP_INT_MAX,
            'entity' => ['type' => 'user', 'id' => 1],
            'start_time' => '10:00',
            'end_time' => '12:00',
        ]);

        $response->assertNotFound();
        $response->assertJson(['error' => 'Shift pivot not found']);
    }

    #[Test]
    public function guest_cannot_update_workflow_settings(): void
    {
        $this->patchJson(route('shift.settings.update.shift-commit-workflow'), [])
            ->assertUnauthorized();
    }

    #[Test]
    public function admin_can_update_workflow_settings(): void
    {
        $this->actingAsAdmin();

        $response = $this->patch(route('shift.settings.update.shift-commit-workflow'), [
            'shift_commit_workflow' => true,
        ]);

        $response->assertRedirect();
    }
}
