<?php

namespace Tests\Feature\Modules\Shift;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Shift\Events\RemoveEntityFormShiftEvent;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Models\ShiftsQualifications;
use Artwork\Modules\Shift\Models\ShiftWorker;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Vacation\Models\VacationConflict;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Anlegen/Bearbeiten/Löschen von Schichten: Gewerksprüfung, gemeinsamer Lösch-Weg (Papierkorb),
 * Gewerk-/Funktions-Löschen und Neubewertung nach Zeit-/Datumsänderung.
 */
final class ShiftMutationHardeningTest extends FeatureTestCase
{
    private function standaloneShift(Craft $craft, array $attributes = []): Shift
    {
        return Shift::factory()->create(array_merge([
            'event_id' => null,
            'room_id' => Room::factory()->create()->id,
            'craft_id' => $craft->id,
            'start_date' => '2026-06-08',
            'end_date' => '2026-06-08',
            'start' => '10:00:00',
            'end' => '14:00:00',
            'break_minutes' => 0,
        ], $attributes));
    }

    private function assignWorker(Shift $shift, User $worker, array $attributes = []): int
    {
        ShiftWorker::create(array_merge([
            'shift_id' => $shift->id,
            'employable_type' => User::class,
            'employable_id' => $worker->id,
            'shift_qualification_id' => ShiftQualification::factory()->create()->id,
            'craft_abbreviation' => 'X',
            'start_date' => $shift->start_date,
            'end_date' => $shift->end_date,
            'start_time' => substr((string) $shift->start, 0, 5),
            'end_time' => substr((string) $shift->end, 0, 5),
        ], $attributes));

        return (int) DB::table('shift_workers')
            ->where('shift_id', $shift->id)
            ->where('employable_id', $worker->id)
            ->value('id');
    }

    private function plannerOf(Craft ...$crafts): User
    {
        $planner = $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);
        foreach ($crafts as $craft) {
            $craft->craftShiftPlaner()->attach($planner->id);
        }

        return $planner;
    }

    #[Test]
    public function planner_of_another_craft_gets_a_clear_message_instead_of_changing_the_shift(): void
    {
        $ownCraft = Craft::factory()->create(['assignable_by_all' => false]);
        $foreignCraft = Craft::factory()->create(['assignable_by_all' => false, 'name' => 'Beleuchtung']);
        $this->plannerOf($ownCraft);
        $shift = $this->standaloneShift($foreignCraft);

        $this->patchJson(route('event.shift.update', $shift), [
            'start_date' => '2026-06-08',
            'end_date' => '2026-06-08',
            'start' => '12:00',
            'end' => '16:00',
            'craft_id' => $foreignCraft->id,
            'room_id' => $shift->room_id,
            'updateOrCreateInShiftPlan' => true,
        ])
            ->assertForbidden()
            ->assertJsonPath('message', __(
                'You are currently not registered as a shift planner for the craft ":crafts".',
                ['crafts' => 'Beleuchtung']
            ));

        $this->deleteJson(route('shifts.destroy', $shift))->assertForbidden();
        $this->assertNotSoftDeleted('shifts', ['id' => $shift->id]);
        $this->assertDatabaseHas('shifts', ['id' => $shift->id, 'start' => '10:00:00']);
    }

    #[Test]
    public function planner_cannot_move_a_shift_into_a_craft_they_do_not_plan(): void
    {
        $ownCraft = Craft::factory()->create(['assignable_by_all' => false]);
        $foreignCraft = Craft::factory()->create(['assignable_by_all' => false]);
        $this->plannerOf($ownCraft);
        $shift = $this->standaloneShift($ownCraft);

        $this->patchJson(route('event.shift.update', $shift), [
            'craft_id' => $foreignCraft->id,
            'room_id' => $shift->room_id,
            'updateOrCreateInShiftPlan' => true,
        ])->assertForbidden();

        $this->assertDatabaseHas('shifts', ['id' => $shift->id, 'craft_id' => $ownCraft->id]);
    }

    #[Test]
    public function planner_can_create_shifts_only_for_own_crafts(): void
    {
        $ownCraft = Craft::factory()->create(['assignable_by_all' => false]);
        $foreignCraft = Craft::factory()->create(['assignable_by_all' => false]);
        $this->plannerOf($ownCraft);
        $room = Room::factory()->create();
        $payload = fn (Craft $craft): array => [
            'craft_id' => $craft->id,
            'room_id' => $room->id,
            'day' => '2026-06-08',
            'start' => '10:00',
            'end' => '14:00',
            'break_minutes' => 0,
            'shiftsQualifications' => [],
        ];

        $this->postJson(route('event.shift.store.without.event'), $payload($foreignCraft))->assertForbidden();
        $this->assertDatabaseMissing('shifts', ['craft_id' => $foreignCraft->id]);

        $this->postJson(route('event.shift.store.without.event'), $payload($ownCraft))->assertSuccessful();
        $this->assertDatabaseHas('shifts', ['craft_id' => $ownCraft->id, 'room_id' => $room->id]);
    }

    #[Test]
    public function creating_a_shift_without_room_is_rejected_with_a_validation_error(): void
    {
        $this->actingAsAdmin();
        $craft = Craft::factory()->create();

        $this->postJson(route('event.shift.store.without.event'), [
            'craft_id' => $craft->id,
            'day' => '2026-06-08',
            'start' => '10:00',
            'end' => '14:00',
            'shiftsQualifications' => [],
        ])->assertUnprocessable()->assertJsonValidationErrors('room_id');
    }

    #[Test]
    public function deleting_moves_the_shift_to_the_trash_and_cleans_up_conflicts(): void
    {
        $this->actingAsAdmin();
        $shift = $this->standaloneShift(Craft::factory()->create());
        $worker = User::factory()->create();
        $this->assignWorker($shift, $worker);
        DB::table('vacation_conflicts')->insert([
            'vacation_id' => 1,
            'shift_id' => $shift->id,
            'user_name' => 'X',
            'date' => '2026-06-08',
            'start_time' => '10:00',
            'end_time' => '14:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->deleteJson(route('shifts.destroy', $shift))
            ->assertOk()
            ->assertJsonPath('removed', true)
            ->assertJsonPath('shift.id', $shift->id)
            ->assertJsonPath('roomId', $shift->room_id);

        $this->assertSoftDeleted('shifts', ['id' => $shift->id]);
        $this->assertSame(0, VacationConflict::query()->where('shift_id', $shift->id)->count());
    }

    #[Test]
    public function deleting_a_craft_removes_its_shifts_and_does_not_fail_on_assigned_people(): void
    {
        $this->actingAsAdmin();
        $craft = Craft::factory()->create();
        $shift = $this->standaloneShift($craft);
        $member = User::factory()->create();
        DB::table('users_assigned_crafts')->insert(['user_id' => $member->id, 'craft_id' => $craft->id]);

        $this->delete(route('craft.delete', $craft))->assertRedirect();

        $this->assertDatabaseMissing('crafts', ['id' => $craft->id]);
        $this->assertDatabaseMissing('shifts', ['id' => $shift->id]);
    }

    #[Test]
    public function a_used_shift_qualification_can_be_deleted_and_its_slots_move_to_the_default(): void
    {
        $this->actingAsAdmin();
        ShiftQualification::query()->firstOrCreate(['id' => 1], ['name' => 'Standard', 'icon' => 'user']);
        $qualification = ShiftQualification::factory()->create();
        $shift = $this->standaloneShift(Craft::factory()->create());
        ShiftsQualifications::factory()->create([
            'shift_id' => $shift->id,
            'shift_qualification_id' => $qualification->id,
            'value' => 3,
        ]);
        $worker = User::factory()->create();
        $pivotId = $this->assignWorker($shift, $worker, ['shift_qualification_id' => $qualification->id]);

        $this->delete(route('shift-qualifications.destroy', $qualification))->assertRedirect();

        $this->assertDatabaseMissing('shifts_qualifications', ['shift_qualification_id' => $qualification->id]);
        $this->assertDatabaseHas('shifts_qualifications', [
            'shift_id' => $shift->id,
            'shift_qualification_id' => 1,
            'value' => 3,
        ]);
        $this->assertDatabaseHas('shift_workers', ['id' => $pivotId, 'shift_qualification_id' => 1]);
    }

    #[Test]
    public function moving_a_shift_to_another_day_moves_individual_times_and_resets_confirmations(): void
    {
        $this->actingAsAdmin();
        $shift = $this->standaloneShift(Craft::factory()->create());
        $withIndividualTime = User::factory()->create();
        $confirmed = User::factory()->create();
        $individualPivotId = $this->assignWorker($shift, $withIndividualTime, [
            'start_time' => '11:00',
            'end_time' => '13:00',
        ]);
        $confirmedPivotId = $this->assignWorker($shift, $confirmed, [
            'confirmation_status' => ShiftWorker::CONFIRMATION_ACCEPTED,
            'confirmation_at' => now(),
        ]);

        $this->patchJson(route('event.shift.update', $shift), [
            'start_date' => '2026-06-10',
            'end_date' => '2026-06-10',
            'start' => '10:00',
            'end' => '14:00',
            'craft_id' => $shift->craft_id,
            'room_id' => $shift->room_id,
            'updateOrCreateInShiftPlan' => true,
        ])->assertOk();

        $this->assertDatabaseHas('shift_workers', [
            'id' => $individualPivotId,
            'start_date' => '2026-06-10',
            'end_date' => '2026-06-10',
            'start_time' => '11:00:00',
            'end_time' => '13:00:00',
        ]);
        $this->assertDatabaseHas('shift_workers', [
            'id' => $confirmedPivotId,
            'start_date' => '2026-06-10',
            'confirmation_status' => null,
        ]);
    }

    #[Test]
    public function removing_a_person_broadcasts_the_person_id_not_the_pivot_id(): void
    {
        $this->actingAsAdmin();
        Event::fake([RemoveEntityFormShiftEvent::class]);
        $shift = $this->standaloneShift(Craft::factory()->create());
        $worker = User::factory()->create();
        $pivotId = $this->assignWorker($shift, $worker);

        $this->deleteJson(route('shift.removeUserByType', ['usersPivotId' => $pivotId, 'userType' => 0]), [
            'removeFromSingleShift' => true,
        ])->assertSuccessful();

        Event::assertDispatched(
            RemoveEntityFormShiftEvent::class,
            static fn (RemoveEntityFormShiftEvent $event): bool => (int) $event->entity === $worker->id
        );
    }
}
