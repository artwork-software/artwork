<?php

namespace Tests\Feature\Modules\Shift;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Models\ShiftsQualifications;
use Artwork\Modules\Shift\Models\ShiftWorker;
use Artwork\Modules\Shift\Services\ShiftDeletionService;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Papierkorb-Tab "Schichten": Liste (Gewerks-Scoping, Suche), Wiederherstellen inkl. Besetzung,
 * endgültig löschen.
 */
final class ShiftTrashTest extends FeatureTestCase
{
    private function trashedShift(Craft $craft, array $attributes = []): Shift
    {
        $shift = Shift::factory()->create(array_merge([
            'event_id' => null,
            'room_id' => Room::factory()->create()->id,
            'craft_id' => $craft->id,
            'start_date' => '2026-06-08',
            'end_date' => '2026-06-08',
            'start' => '10:00:00',
            'end' => '14:00:00',
        ], $attributes));

        return $shift;
    }

    private function planner(Craft ...$crafts): User
    {
        $planner = $this->actingAsUserWith([
            PermissionEnum::SHIFT_PLANNER->value,
            'can access trash',
        ]);
        foreach ($crafts as $craft) {
            $craft->craftShiftPlaner()->attach($planner->id);
        }

        return $planner;
    }

    #[Test]
    public function planner_sees_only_trashed_standalone_shifts_of_own_crafts(): void
    {
        $ownCraft = Craft::factory()->create(['assignable_by_all' => false, 'name' => 'Ton']);
        $foreignCraft = Craft::factory()->create(['assignable_by_all' => false]);
        $this->planner($ownCraft);

        $visible = $this->trashedShift($ownCraft, ['description' => 'Soundcheck']);
        $foreign = $this->trashedShift($foreignCraft);
        $active = $this->trashedShift($ownCraft);
        $visible->delete();
        $foreign->delete();

        $this->get(route('shifts.trashed'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Trash/Shifts')
                ->where('trashed_shifts.total', 1)
                ->where('trashed_shifts.data.0.id', $visible->id)
                ->where('trashed_shifts.data.0.craft.name', 'Ton'));

        $this->get(route('shifts.trashed', ['search' => 'nichts-passt']))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('trashed_shifts.total', 0));
        $this->get(route('shifts.trashed', ['search' => 'Soundcheck']))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('trashed_shifts.total', 1));

        $this->assertNotSoftDeleted('shifts', ['id' => $active->id]);
    }

    #[Test]
    public function restoring_brings_back_slots_and_people_deleted_with_the_shift(): void
    {
        $this->actingAsAdmin();
        $shift = $this->trashedShift(Craft::factory()->create());
        $qualification = ShiftQualification::factory()->create();
        $slot = ShiftsQualifications::factory()->create([
            'shift_id' => $shift->id,
            'shift_qualification_id' => $qualification->id,
            'value' => 2,
        ]);
        $removedEarlier = ShiftsQualifications::factory()->create([
            'shift_id' => $shift->id,
            'shift_qualification_id' => ShiftQualification::factory()->create()->id,
            'value' => 1,
        ]);
        // früher einzeln entfernter Platz — darf beim Wiederherstellen NICHT zurückkommen
        DB::table('shifts_qualifications')->where('id', $removedEarlier->id)->update(['deleted_at' => now()->subDay()]);

        $worker = User::factory()->create();
        ShiftWorker::create([
            'shift_id' => $shift->id,
            'employable_type' => User::class,
            'employable_id' => $worker->id,
            'shift_qualification_id' => $qualification->id,
            'craft_abbreviation' => 'X',
            'start_date' => '2026-06-08',
            'end_date' => '2026-06-08',
            'start_time' => '10:00',
            'end_time' => '14:00',
        ]);

        app(ShiftDeletionService::class)->delete($shift);
        $this->assertSoftDeleted('shifts', ['id' => $shift->id]);

        $this->patch(route('shifts.trashed.restore', ['shiftId' => $shift->id]))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertNotSoftDeleted('shifts', ['id' => $shift->id]);
        $this->assertNotSoftDeleted('shifts_qualifications', ['id' => $slot->id]);
        $this->assertSoftDeleted('shifts_qualifications', ['id' => $removedEarlier->id]);
        $this->assertDatabaseHas('shift_workers', [
            'shift_id' => $shift->id,
            'employable_id' => $worker->id,
            'deleted_at' => null,
        ]);
    }

    #[Test]
    public function restore_is_refused_while_the_room_is_in_the_recycle_bin(): void
    {
        $this->actingAsAdmin();
        $shift = $this->trashedShift(Craft::factory()->create());
        $shift->delete();
        Room::query()->whereKey($shift->room_id)->first()->delete();

        $this->patch(route('shifts.trashed.restore', ['shiftId' => $shift->id]))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSoftDeleted('shifts', ['id' => $shift->id]);
    }

    #[Test]
    public function planner_cannot_restore_or_delete_shifts_of_foreign_crafts(): void
    {
        $ownCraft = Craft::factory()->create(['assignable_by_all' => false]);
        $foreignCraft = Craft::factory()->create(['assignable_by_all' => false]);
        $this->planner($ownCraft);
        $foreign = $this->trashedShift($foreignCraft);
        $foreign->delete();

        $this->patch(route('shifts.trashed.restore', ['shiftId' => $foreign->id]))->assertSessionHas('error');
        $this->delete(route('shifts.trashed.force', ['shiftId' => $foreign->id]))->assertSessionHas('error');

        $this->assertSoftDeleted('shifts', ['id' => $foreign->id]);
    }

    #[Test]
    public function force_delete_all_only_removes_shifts_the_planner_may_plan(): void
    {
        $ownCraft = Craft::factory()->create(['assignable_by_all' => false]);
        $foreignCraft = Craft::factory()->create(['assignable_by_all' => false]);
        $this->planner($ownCraft);
        $own = $this->trashedShift($ownCraft);
        $foreign = $this->trashedShift($foreignCraft);
        $own->delete();
        $foreign->delete();

        $this->delete(route('shifts.trashed.force-all'))->assertRedirect();

        $this->assertDatabaseMissing('shifts', ['id' => $own->id]);
        $this->assertSoftDeleted('shifts', ['id' => $foreign->id]);
    }

    #[Test]
    public function trash_access_without_shift_planning_is_forbidden(): void
    {
        $this->actingAsUserWith('can access trash');

        $this->get(route('shifts.trashed'))->assertForbidden();
    }

    #[Test]
    public function shifts_are_pruned_one_month_after_deletion(): void
    {
        $craft = Craft::factory()->create();
        $old = $this->trashedShift($craft);
        $recent = $this->trashedShift($craft);
        $old->delete();
        $recent->delete();
        DB::table('shifts')->where('id', $old->id)->update(['deleted_at' => now()->subMonths(2)]);

        $prunableIds = (new Shift())->prunable()->pluck('id')->all();

        $this->assertContains($old->id, $prunableIds);
        $this->assertNotContains($recent->id, $prunableIds);
    }
}
