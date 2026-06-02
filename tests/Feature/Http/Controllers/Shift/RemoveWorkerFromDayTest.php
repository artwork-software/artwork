<?php

namespace Tests\Feature\Http\Controllers\Shift;

use Artwork\Modules\IndividualTimes\Models\IndividualTime;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftWorker;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class RemoveWorkerFromDayTest extends FeatureTestCase
{
    private function assignUserToShift(Shift $shift, User $user): ShiftWorker
    {
        ShiftWorker::query()->create([
            'shift_id' => $shift->id,
            'employable_type' => User::class,
            'employable_id' => $user->id,
            'shift_qualification_id' => ShiftQualification::factory()->create()->id,
            'shift_count' => 1,
        ]);

        // ShiftWorker is a MorphPivot (incrementing = false), so re-query to obtain the row id.
        return ShiftWorker::query()
            ->where('shift_id', $shift->id)
            ->where('employable_type', User::class)
            ->where('employable_id', $user->id)
            ->latest('id')
            ->firstOrFail();
    }

    private function individualTimeFor(User $user, string $date): IndividualTime
    {
        return IndividualTime::query()->create([
            'title' => 'Setup',
            'start_date' => $date,
            'end_date' => $date,
            'start_time' => '08:00',
            'end_time' => '12:00',
            'full_day' => false,
            'timeable_type' => User::class,
            'timeable_id' => $user->id,
        ]);
    }

    #[Test]
    public function guest_cannot_remove_worker_from_day(): void
    {
        $this->postJson(route('shift.removeWorkerFromDay'), [])
            ->assertUnauthorized();
    }

    #[Test]
    public function day_assignments_returns_active_shifts_and_individual_times_for_the_day(): void
    {
        $this->actingAsAdmin();

        $target = User::factory()->create();
        $shift = Shift::factory()->create([
            'start_date' => '2026-06-02',
            'end_date' => '2026-06-02',
        ]);
        $pivot = $this->assignUserToShift($shift, $target);
        $this->individualTimeFor($target, '2026-06-02');

        $response = $this->getJson(route('shift.dayAssignments', [
            'model_type' => 0,
            'model_id' => $target->id,
            'date' => '2026-06-02',
        ]));

        $response->assertSuccessful()
            ->assertJsonCount(1, 'shifts')
            ->assertJsonCount(1, 'individual_times')
            ->assertJsonPath('shifts.0.pivot_id', $pivot->id);
    }

    #[Test]
    public function day_assignments_is_empty_when_nothing_covers_the_day(): void
    {
        $this->actingAsAdmin();

        $target = User::factory()->create();
        $shift = Shift::factory()->create([
            'start_date' => '2026-06-02',
            'end_date' => '2026-06-02',
        ]);
        $this->assignUserToShift($shift, $target);

        $response = $this->getJson(route('shift.dayAssignments', [
            'model_type' => 0,
            'model_id' => $target->id,
            'date' => '2026-06-10',
        ]));

        $response->assertSuccessful()
            ->assertJsonCount(0, 'shifts')
            ->assertJsonCount(0, 'individual_times');
    }

    #[Test]
    public function planner_removes_the_listed_shift_assignment_and_individual_time(): void
    {
        $this->actingAsAdmin();

        $target = User::factory()->create();
        $shift = Shift::factory()->create();
        $pivot = $this->assignUserToShift($shift, $target);
        $individualTime = $this->individualTimeFor($target, '2026-06-02');

        $response = $this->postJson(route('shift.removeWorkerFromDay'), [
            'model_type' => 0,
            'model_id' => $target->id,
            'shift_pivot_ids' => [$pivot->id],
            'individual_time_ids' => [$individualTime->id],
        ]);

        $response->assertSuccessful()->assertJson(['success' => true]);
        $this->assertDatabaseMissing('shift_workers', ['id' => $pivot->id]);
        $this->assertDatabaseMissing('individual_times', ['id' => $individualTime->id]);
    }

    #[Test]
    public function setting_not_available_keeps_the_shift_when_remove_from_shifts_is_false(): void
    {
        $this->actingAsAdmin();

        $target = User::factory()->create();
        $shift = Shift::factory()->create([
            'start_date' => '2026-06-02',
            'end_date' => '2026-06-02',
            'event_start_day' => '2026-06-02',
        ]);
        $pivot = $this->assignUserToShift($shift, $target);

        $this->patchJson(route('user.check.vacation', ['user' => $target->id]), [
            'checked' => ['name' => 'Nicht Verfügbar', 'type' => 'NOT_AVAILABLE'],
            'day' => '2026-06-02',
            'vacationTypeBeforeUpdate' => ['name' => 'Verfügbar', 'type' => 'AVAILABLE'],
            'remove_from_shifts' => false,
        ])->assertSuccessful();

        // "No, only change status" -> the assignment must remain.
        $this->assertTrue(
            $target->fresh()->shifts()->where('shifts.id', $shift->id)->exists(),
            'User should still be assigned to the shift.',
        );
    }

    #[Test]
    public function setting_not_available_removes_shifts_by_default(): void
    {
        $this->actingAsAdmin();

        $target = User::factory()->create();
        $shift = Shift::factory()->create([
            'start_date' => '2026-06-02',
            'end_date' => '2026-06-02',
            'event_start_day' => '2026-06-02',
        ]);
        $pivot = $this->assignUserToShift($shift, $target);

        // No remove_from_shifts flag -> legacy behaviour: auto-detach.
        $this->patchJson(route('user.check.vacation', ['user' => $target->id]), [
            'checked' => ['name' => 'Nicht Verfügbar', 'type' => 'NOT_AVAILABLE'],
            'day' => '2026-06-02',
            'vacationTypeBeforeUpdate' => ['name' => 'Verfügbar', 'type' => 'AVAILABLE'],
        ])->assertSuccessful();

        $this->assertFalse(
            $target->fresh()->shifts()->where('shifts.id', $shift->id)->exists(),
            'User should no longer be assigned to the shift (legacy auto-detach).',
        );
    }

    #[Test]
    public function it_does_not_touch_assignments_or_times_of_a_different_worker(): void
    {
        $this->actingAsAdmin();

        $target = User::factory()->create();
        $other = User::factory()->create();
        $shift = Shift::factory()->create();

        $otherPivot = $this->assignUserToShift($shift, $other);
        $otherTime = $this->individualTimeFor($other, '2026-06-02');

        // Pivot/time ids are passed but belong to $other, while we act on $target.
        $response = $this->postJson(route('shift.removeWorkerFromDay'), [
            'model_type' => 0,
            'model_id' => $target->id,
            'shift_pivot_ids' => [$otherPivot->id],
            'individual_time_ids' => [$otherTime->id],
        ]);

        $response->assertSuccessful();
        $this->assertDatabaseHas('shift_workers', ['id' => $otherPivot->id]);
        $this->assertDatabaseHas('individual_times', ['id' => $otherTime->id]);
    }
}
