<?php

namespace Tests\Feature\Http\Controllers\Shift;

use Artwork\Modules\Calendar\DTO\CalendarShiftDTO;
use Artwork\Modules\Calendar\DTO\ShiftDTO;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Shift\Events\UpdateShiftInShiftPlan;
use Artwork\Modules\Shift\Models\GlobalQualification;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftCommitWorkflowUser;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Models\ShiftsQualifications;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Support\Facades\Event;
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
    public function updating_a_shift_from_the_shift_plan_returns_json_instead_of_a_redirect(): void
    {
        // Regression: Der Dienstplan speichert per axios (kein Inertia-Request). Ein 302 wurde vom
        // Browser mit PATCH auf /shifts/view weiterverfolgt -> 405 "PATCH not supported".
        $this->actingAsAdmin();

        $shift = Shift::factory()->create([
            'is_committed' => false,
            'start_date' => '2026-06-08',
            'end_date' => '2026-06-08',
            'start' => '10:00:00',
            'end' => '14:00:00',
        ]);

        $response = $this->patchJson(route('event.shift.update', $shift), [
            'start_date' => '2026-06-08',
            'end_date' => '2026-06-08',
            'start' => '12:00:00',
            'end' => '16:00:00',
            'break_minutes' => 0,
            'craft_id' => $shift->craft_id,
            'updateOrCreateInShiftPlan' => true,
        ]);

        $response->assertOk()->assertJson(['id' => $shift->id]);
        $this->assertDatabaseHas('shifts', ['id' => $shift->id, 'start' => '12:00:00', 'end' => '16:00:00']);
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

    private function createStandaloneShift(array $attributes = []): Shift
    {
        return Shift::factory()->create(array_merge([
            'event_id' => null,
            'room_id' => Room::factory()->create()->id,
            'is_committed' => false,
            'start_date' => '2026-06-08',
            'end_date' => '2026-06-08',
            'start' => '10:00:00',
            'end' => '14:00:00',
        ], $attributes));
    }

    /**
     * @return array<string, mixed>
     */
    private function shiftPlanPayload(Shift $shift, array $overrides = []): array
    {
        return array_merge([
            'start_date' => '2026-06-08',
            'end_date' => '2026-06-08',
            'start' => '10:00',
            'end' => '14:00',
            'break_minutes' => 0,
            'craft_id' => $shift->craft_id,
            'room_id' => $shift->room_id,
            'project_id' => $shift->project_id,
            'updateOrCreateInShiftPlan' => true,
        ], $overrides);
    }

    #[Test]
    public function changing_the_room_keeps_the_project_and_broadcasts_to_old_and_new_room(): void
    {
        $this->actingAsAdmin();
        Event::fake([UpdateShiftInShiftPlan::class]);

        $project = Project::factory()->create();
        $shift = $this->createStandaloneShift(['project_id' => $project->id]);
        $oldRoomId = $shift->room_id;
        $newRoom = Room::factory()->create();

        $this->patchJson(
            route('event.shift.update', $shift),
            $this->shiftPlanPayload($shift, ['room_id' => $newRoom->id])
        )->assertOk();

        $this->assertDatabaseHas('shifts', [
            'id' => $shift->id,
            'room_id' => $newRoom->id,
            'project_id' => $project->id,
        ]);

        Event::assertDispatched(UpdateShiftInShiftPlan::class, function (UpdateShiftInShiftPlan $event) use ($newRoom, $oldRoomId): bool {
            $channels = array_map(static fn ($channel) => $channel->name, $event->broadcastOn());

            return $channels === ['private-shift-plan.room.' . $newRoom->id, 'private-shift-plan.room.' . $oldRoomId]
                && $event->broadcastWith()['previousRoomId'] === $oldRoomId;
        });
    }

    #[Test]
    public function unchanged_room_broadcasts_only_to_the_current_room(): void
    {
        $shift = $this->createStandaloneShift();

        $channels = (new UpdateShiftInShiftPlan($shift, $shift->room_id, $shift->room_id))->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertSame('private-shift-plan.room.' . $shift->room_id, $channels[0]->name);
    }

    #[Test]
    public function standalone_shift_cannot_lose_its_room_through_a_null_value(): void
    {
        // Regression: room_id = null wurde gespeichert, danach warf der Broadcast einen TypeError (500) —
        // die Schicht war aus dem Dienstplan verschwunden und ließ sich nicht mehr löschen.
        $this->actingAsAdmin();
        $shift = $this->createStandaloneShift();

        $this->patchJson(route('event.shift.update', $shift), $this->shiftPlanPayload($shift, ['room_id' => null]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('room_id');

        $this->assertDatabaseHas('shifts', ['id' => $shift->id, 'room_id' => $shift->room_id]);
    }

    #[Test]
    public function unknown_project_or_shift_group_is_rejected_instead_of_a_server_error(): void
    {
        $this->actingAsAdmin();
        $shift = $this->createStandaloneShift();

        $this->patchJson(route('event.shift.update', $shift), $this->shiftPlanPayload($shift, [
            'project_id' => PHP_INT_MAX,
            'shift_group_id' => PHP_INT_MAX,
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['project_id', 'shift_group_id']);
    }

    #[Test]
    public function shift_dto_keeps_the_project_id_of_a_trashed_project(): void
    {
        // Regression: projectId kam aus dem geladenen Projekt — bei einem Projekt im Papierkorb null,
        // das Bearbeiten-Modal speicherte dann project_id = null und die Zuordnung war weg.
        $project = Project::factory()->create();
        $shift = $this->createStandaloneShift(['project_id' => $project->id]);
        $project->delete();

        $shift = $shift->fresh();

        $this->assertSame($project->id, ShiftDTO::fromModel($shift)->projectId);
        $this->assertSame($project->id, CalendarShiftDTO::fromModel($shift)->projectId);
    }

    #[Test]
    public function shift_without_room_can_still_be_deleted(): void
    {
        $this->actingAsAdmin();
        $shift = $this->createStandaloneShift(['room_id' => null]);

        $this->delete(route('shifts.destroy', $shift))->assertSuccessful();

        $this->assertDatabaseMissing('shifts', ['id' => $shift->id]);
    }

    #[Test]
    public function shift_plan_update_returns_the_saved_state_for_the_view(): void
    {
        // Die Ansicht des Speichernden übernimmt die Antwort direkt — unabhängig vom WebSocket
        $this->actingAsAdmin();
        $project = Project::factory()->create();
        $shift = $this->createStandaloneShift(['project_id' => $project->id]);
        $newRoom = Room::factory()->create();

        $this->patchJson(
            route('event.shift.update', $shift),
            $this->shiftPlanPayload($shift, ['room_id' => $newRoom->id])
        )
            ->assertOk()
            ->assertJsonPath('id', $shift->id)
            ->assertJsonPath('roomId', $newRoom->id)
            ->assertJsonPath('previousRoomId', $shift->room_id)
            ->assertJsonPath('shift.roomId', $newRoom->id)
            ->assertJsonPath('shift.projectId', $project->id)
            ->assertJsonPath('lookups.projectsById.' . $project->id . '.name', $project->name);
    }

    #[Test]
    public function global_qualification_set_to_zero_is_removed(): void
    {
        // Regression: Eine auf 0 gesetzte (letzte) globale Qualifikation kam als leere Liste an —
        // der Server ließ die alte Menge stehen.
        $this->actingAsAdmin();
        $shift = $this->createStandaloneShift();
        $globalQualification = GlobalQualification::factory()->create();
        $shift->globalQualifications()->attach($globalQualification->id, ['quantity' => 2]);

        $this->patchJson(route('event.shift.update', $shift), $this->shiftPlanPayload($shift, [
            'globalQualifications' => [
                ['global_qualification_id' => $globalQualification->id, 'quantity' => 0],
            ],
        ]))->assertOk();

        $this->assertDatabaseMissing('shift_global_qualifications', [
            'shift_id' => $shift->id,
            'global_qualification_id' => $globalQualification->id,
        ]);
    }

    #[Test]
    public function update_without_global_qualifications_keeps_existing_ones(): void
    {
        $this->actingAsAdmin();
        $shift = $this->createStandaloneShift();
        $globalQualification = GlobalQualification::factory()->create();
        $shift->globalQualifications()->attach($globalQualification->id, ['quantity' => 2]);

        $this->patchJson(route('event.shift.update', $shift), $this->shiftPlanPayload($shift, [
            'globalQualifications' => [],
        ]))->assertOk();

        $this->assertDatabaseHas('shift_global_qualifications', [
            'shift_id' => $shift->id,
            'global_qualification_id' => $globalQualification->id,
            'quantity' => 2,
        ]);
    }
}
