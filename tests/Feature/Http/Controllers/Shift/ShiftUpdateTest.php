<?php

namespace Tests\Feature\Http\Controllers\Shift;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftCommitWorkflowUser;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Models\ShiftsQualifications;
use Artwork\Modules\User\Models\User;
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
    public function updating_a_shift_without_qualifications_field_preserves_slots_and_assignments(): void
    {
        // Regression: Beim zeitlichen Verschieben einer Schicht wird das Feld `shiftsQualifications`
        // nicht mitgeschickt. Das darf weder die Schichtplätze noch die Zuweisungen löschen.
        $this->actingAsAdmin();

        $shift = Shift::factory()->create([
            'is_committed' => false,
            'start_date' => '2026-06-08',
            'end_date' => '2026-06-08',
            'start' => '10:00:00',
            'end' => '14:00:00',
        ]);

        $shiftQualification = ShiftQualification::factory()->create();
        ShiftsQualifications::factory()->create([
            'shift_id' => $shift->id,
            'shift_qualification_id' => $shiftQualification->id,
            'value' => 2,
        ]);

        $user = User::factory()->create();
        $shift->users()->attach($user->id, [
            'shift_qualification_id' => $shiftQualification->id,
            'start_date' => '2026-06-08',
            'end_date' => '2026-06-08',
            'start_time' => '10:00:00',
            'end_time' => '14:00:00',
        ]);

        // Schicht zeitlich verschieben, OHNE shiftsQualifications mitzuschicken
        $this->patch(route('event.shift.update', $shift), [
            'start_date' => '2026-06-08',
            'end_date' => '2026-06-08',
            'start' => '12:00:00',
            'end' => '16:00:00',
            'break_minutes' => 0,
            'craft_id' => $shift->craft_id,
        ]);

        // Schichtplatz bleibt erhalten
        $this->assertDatabaseHas('shifts_qualifications', [
            'shift_id' => $shift->id,
            'shift_qualification_id' => $shiftQualification->id,
            'value' => 2,
        ]);

        // Zuweisung (Source of Truth) bleibt erhalten
        $this->assertDatabaseHas('shift_workers', [
            'shift_id' => $shift->id,
            'employable_id' => $user->id,
            'employable_type' => User::class,
            'deleted_at' => null,
        ]);
    }

    #[Test]
    public function updating_a_shift_with_explicitly_empty_qualifications_clears_slots_and_assignments(): void
    {
        // Gegenprobe: Wird `shiftsQualifications` bewusst leer mitgeschickt (User hat alle
        // Plätze entfernt), sollen Schichtplätze und Zuweisungen weiterhin gelöscht werden.
        $this->actingAsAdmin();

        $shift = Shift::factory()->create([
            'is_committed' => false,
            'start_date' => '2026-06-08',
            'end_date' => '2026-06-08',
            'start' => '10:00:00',
            'end' => '14:00:00',
        ]);

        $shiftQualification = ShiftQualification::factory()->create();
        ShiftsQualifications::factory()->create([
            'shift_id' => $shift->id,
            'shift_qualification_id' => $shiftQualification->id,
            'value' => 2,
        ]);

        $user = User::factory()->create();
        $shift->users()->attach($user->id, [
            'shift_qualification_id' => $shiftQualification->id,
            'start_date' => '2026-06-08',
            'end_date' => '2026-06-08',
            'start_time' => '10:00:00',
            'end_time' => '14:00:00',
        ]);

        $this->patch(route('event.shift.update', $shift), [
            'start_date' => '2026-06-08',
            'end_date' => '2026-06-08',
            'start' => '10:00:00',
            'end' => '14:00:00',
            'break_minutes' => 0,
            'craft_id' => $shift->craft_id,
            'shiftsQualifications' => [],
        ]);

        $this->assertDatabaseMissing('shifts_qualifications', [
            'shift_id' => $shift->id,
            'shift_qualification_id' => $shiftQualification->id,
            'deleted_at' => null,
        ]);

        $this->assertDatabaseMissing('shift_workers', [
            'shift_id' => $shift->id,
            'employable_id' => $user->id,
            'deleted_at' => null,
        ]);
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
        // Aktivieren verlangt seit Block 1a mindestens eine Genehmiger:in
        ShiftCommitWorkflowUser::create(['user_id' => User::factory()->create()->id]);

        $response = $this->patch(route('shift.settings.update.shift-commit-workflow'), [
            'shift_commit_workflow' => true,
        ]);

        $response->assertRedirect();
    }
}
