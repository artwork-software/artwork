<?php

namespace Tests\Feature\Modules\Shift;

use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Services\ShiftAssignmentPreflightService;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Vacation\Enums\Vacation as VacationType;
use Artwork\Modules\Vacation\Models\Vacation;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Block 5A: Vorabprüfung vor dem Drop (shift.assignment-preflight) — Überschneidung
 * (Pivot-Zeiten, Mitternacht), Urlaub/freier Tag, nicht verfügbar; nur Warnung.
 */
final class ShiftAssignmentPreflightTest extends FeatureTestCase
{
    private function createShift(string $startDate, string $start, string $end, ?string $endDate = null): Shift
    {
        return Shift::factory()->create([
            'start_date' => $startDate,
            'end_date' => $endDate ?? $startDate,
            'start' => $start,
            'end' => $end,
        ]);
    }

    private function assign(Shift $shift, User|Freelancer $worker, array $pivot = []): void
    {
        $relation = $worker instanceof User ? $shift->users() : $shift->freelancer();
        $relation->attach($worker->id, array_merge([
            'shift_qualification_id' => ShiftQualification::factory()->create()->id,
        ], $pivot));
    }

    private function preflight(Shift $shift, User|Freelancer $worker, array $extra = []): \Illuminate\Testing\TestResponse
    {
        return $this->postJson(route('shift.assignment-preflight'), array_merge([
            'shift_id' => $shift->id,
            'employable_type' => $worker instanceof User ? 'user' : 'freelancer',
            'employable_id' => $worker->id,
        ], $extra));
    }

    private function conflictTypes(\Illuminate\Testing\TestResponse $response): array
    {
        return array_column($response->json('conflicts'), 'type');
    }

    #[Test]
    public function overlapping_assignment_on_the_same_day_is_reported(): void
    {
        $this->actingAsUserWith('can plan shifts');
        $worker = User::factory()->create();
        $target = $this->createShift('2026-09-14', '10:00', '18:00');
        $this->assign($this->createShift('2026-09-14', '12:00', '20:00'), $worker);

        $response = $this->preflight($target, $worker)->assertOk();

        $this->assertSame([ShiftAssignmentPreflightService::TYPE_OVERLAP], $this->conflictTypes($response));
        $this->assertStringContainsString('14.09.2026 12:00–20:00', $response->json('conflicts.0.detail'));
        $this->assertSame($worker->full_name, $response->json('person.name'));
        $this->assertSame('14.09.2026', $response->json('shift.date'));
        $this->assertSame('10:00', $response->json('shift.start'));
        $this->assertSame('18:00', $response->json('shift.end'));
    }

    #[Test]
    public function pivot_times_take_precedence_over_shift_times(): void
    {
        $this->actingAsUserWith('can plan shifts');
        $worker = User::factory()->create();
        $target = $this->createShift('2026-09-14', '10:00', '18:00');

        // Schicht überschneidet laut Schichtzeit, die individuelle Zeit liegt aber davor
        $this->assign($this->createShift('2026-09-14', '08:00', '12:00'), $worker, [
            'start_time' => '05:00',
            'end_time' => '09:00',
        ]);

        $this->assertSame([], $this->conflictTypes($this->preflight($target, $worker)->assertOk()));

        // Schicht liegt laut Schichtzeit danach, die individuelle Zeit ragt aber hinein
        $this->assign($this->createShift('2026-09-14', '19:00', '22:00'), $worker, [
            'start_time' => '17:00',
            'end_time' => '20:00',
        ]);

        $this->assertSame(
            [ShiftAssignmentPreflightService::TYPE_OVERLAP],
            $this->conflictTypes($this->preflight($target, $worker)->assertOk())
        );
    }

    #[Test]
    public function overlap_is_detected_across_midnight(): void
    {
        $this->actingAsUserWith('can plan shifts');
        $worker = User::factory()->create();
        // Nachtschicht 22:00–06:00 (Ende am Folgetag)
        $target = $this->createShift('2026-09-14', '22:00', '06:00', '2026-09-15');

        $this->assign($this->createShift('2026-09-15', '05:00', '09:00'), $worker);
        $this->assertSame(
            [ShiftAssignmentPreflightService::TYPE_OVERLAP],
            $this->conflictTypes($this->preflight($target, $worker)->assertOk())
        );

        // Andere Person: Frühschicht erst nach Ende der Nachtschicht → kein Konflikt
        $other = User::factory()->create();
        $this->assign($this->createShift('2026-09-15', '07:00', '10:00'), $other);
        $this->assertSame([], $this->conflictTypes($this->preflight($target, $other)->assertOk()));

        // Mitternachtsschicht mit gleichem Start-/Enddatum (Ende <= Start → Folgetag)
        $sameDateTarget = $this->createShift('2026-09-14', '22:00', '06:00');
        $this->assertSame(
            [ShiftAssignmentPreflightService::TYPE_OVERLAP],
            $this->conflictTypes($this->preflight($sameDateTarget, $worker)->assertOk())
        );
    }

    #[Test]
    public function shift_itself_and_adjacent_shifts_do_not_count_as_overlap(): void
    {
        $this->actingAsUserWith('can plan shifts');
        $worker = User::factory()->create();
        $target = $this->createShift('2026-09-14', '10:00', '18:00');
        $this->assign($target, $worker);
        $this->assign($this->createShift('2026-09-14', '18:00', '22:00'), $worker);
        $this->assign($this->createShift('2026-09-14', '06:00', '10:00'), $worker);

        $this->assertSame([], $this->conflictTypes($this->preflight($target, $worker)->assertOk()));
    }

    #[Test]
    public function vacation_and_free_day_are_reported(): void
    {
        $this->actingAsUserWith('can plan shifts');
        $worker = User::factory()->create();
        $target = $this->createShift('2026-09-14', '10:00', '18:00');

        Vacation::factory()->create([
            'vacationer_type' => User::class,
            'vacationer_id' => $worker->id,
            'date' => '2026-09-14',
            'full_day' => true,
            'is_series' => false,
            'type' => VacationType::OFF_WORK,
        ]);

        $response = $this->preflight($target, $worker)->assertOk();
        $this->assertSame([ShiftAssignmentPreflightService::TYPE_VACATION], $this->conflictTypes($response));
        $this->assertSame('14.09.2026', $response->json('conflicts.0.detail'));

        $freeDayWorker = User::factory()->create();
        Vacation::factory()->create([
            'vacationer_type' => User::class,
            'vacationer_id' => $freeDayWorker->id,
            'date' => '2026-09-14',
            'full_day' => true,
            'is_series' => false,
            'type' => VacationType::FREE_WORK,
        ]);
        $this->assertSame(
            [ShiftAssignmentPreflightService::TYPE_VACATION],
            $this->conflictTypes($this->preflight($target, $freeDayWorker)->assertOk())
        );

        // Urlaub an einem anderen Tag ist kein Konflikt
        $otherDayWorker = User::factory()->create();
        Vacation::factory()->create([
            'vacationer_type' => User::class,
            'vacationer_id' => $otherDayWorker->id,
            'date' => '2026-09-15',
            'full_day' => true,
            'is_series' => false,
            'type' => VacationType::OFF_WORK,
        ]);
        $this->assertSame([], $this->conflictTypes($this->preflight($target, $otherDayWorker)->assertOk()));
    }

    #[Test]
    public function not_available_full_day_and_overlapping_time_window_are_reported(): void
    {
        $this->actingAsUserWith('can plan shifts');
        $target = $this->createShift('2026-09-14', '10:00', '18:00');

        $fullDay = User::factory()->create();
        Vacation::factory()->create([
            'vacationer_type' => User::class,
            'vacationer_id' => $fullDay->id,
            'date' => '2026-09-14',
            'full_day' => true,
            'is_series' => false,
            'type' => VacationType::NOT_AVAILABLE,
        ]);
        $this->assertSame(
            [ShiftAssignmentPreflightService::TYPE_UNAVAILABLE],
            $this->conflictTypes($this->preflight($target, $fullDay)->assertOk())
        );

        $overlappingWindow = User::factory()->create();
        Vacation::factory()->create([
            'vacationer_type' => User::class,
            'vacationer_id' => $overlappingWindow->id,
            'date' => '2026-09-14',
            'full_day' => false,
            'start_time' => '12:00',
            'end_time' => '14:00',
            'is_series' => false,
            'type' => VacationType::NOT_AVAILABLE,
        ]);
        $response = $this->preflight($target, $overlappingWindow)->assertOk();
        $this->assertSame([ShiftAssignmentPreflightService::TYPE_UNAVAILABLE], $this->conflictTypes($response));
        $this->assertSame('14.09.2026 12:00–14:00', $response->json('conflicts.0.detail'));

        $disjointWindow = User::factory()->create();
        Vacation::factory()->create([
            'vacationer_type' => User::class,
            'vacationer_id' => $disjointWindow->id,
            'date' => '2026-09-14',
            'full_day' => false,
            'start_time' => '19:00',
            'end_time' => '21:00',
            'is_series' => false,
            'type' => VacationType::NOT_AVAILABLE,
        ]);
        $this->assertSame([], $this->conflictTypes($this->preflight($target, $disjointWindow)->assertOk()));
    }

    #[Test]
    public function effective_times_from_the_request_override_the_shift_times(): void
    {
        $this->actingAsUserWith('can plan shifts');
        $worker = User::factory()->create();
        $target = $this->createShift('2026-09-14', '10:00', '18:00');
        $this->assign($this->createShift('2026-09-14', '19:00', '22:00'), $worker);

        $this->assertSame([], $this->conflictTypes($this->preflight($target, $worker)->assertOk()));
        $this->assertSame(
            [ShiftAssignmentPreflightService::TYPE_OVERLAP],
            $this->conflictTypes($this->preflight($target, $worker, ['start' => '14:00', 'end' => '20:00'])->assertOk())
        );
    }

    #[Test]
    public function freelancers_are_checked_as_well(): void
    {
        $this->actingAsUserWith('can plan shifts');
        $freelancer = Freelancer::factory()->create();
        $target = $this->createShift('2026-09-14', '10:00', '18:00');
        $this->assign($this->createShift('2026-09-14', '16:00', '20:00'), $freelancer);

        $this->assertSame(
            [ShiftAssignmentPreflightService::TYPE_OVERLAP],
            $this->conflictTypes($this->preflight($target, $freelancer)->assertOk())
        );
    }

    #[Test]
    public function multiple_conflicts_are_all_listed_and_no_conflicts_yield_an_empty_list(): void
    {
        $this->actingAsUserWith('can plan shifts');
        $worker = User::factory()->create();
        $target = $this->createShift('2026-09-14', '10:00', '18:00');

        $this->assertSame([], $this->preflight($target, $worker)->assertOk()->json('conflicts'));

        $this->assign($this->createShift('2026-09-14', '09:00', '11:00'), $worker);
        Vacation::factory()->create([
            'vacationer_type' => User::class,
            'vacationer_id' => $worker->id,
            'date' => '2026-09-14',
            'full_day' => true,
            'is_series' => false,
            'type' => VacationType::OFF_WORK,
        ]);

        $this->assertSame(
            [ShiftAssignmentPreflightService::TYPE_OVERLAP, ShiftAssignmentPreflightService::TYPE_VACATION],
            $this->conflictTypes($this->preflight($target, $worker)->assertOk())
        );
    }

    #[Test]
    public function preflight_requires_the_plan_shifts_permission(): void
    {
        $worker = User::factory()->create();
        $target = $this->createShift('2026-09-14', '10:00', '18:00');

        $this->actingAs(User::factory()->create());
        $this->preflight($target, $worker)->assertForbidden();

        $this->actingAsAdmin();
        $this->preflight($target, $worker)->assertOk();
    }

    #[Test]
    public function preflight_validates_its_input(): void
    {
        $this->actingAsUserWith('can plan shifts');

        $this->postJson(route('shift.assignment-preflight'), [
            'shift_id' => 999999,
            'employable_type' => 'user',
            'employable_id' => 1,
        ])->assertStatus(422)->assertJsonValidationErrors(['shift_id']);

        $target = $this->createShift('2026-09-14', '10:00', '18:00');
        $this->postJson(route('shift.assignment-preflight'), [
            'shift_id' => $target->id,
            'employable_type' => 'robot',
            'employable_id' => 1,
        ])->assertStatus(422)->assertJsonValidationErrors(['employable_type']);
    }
}
