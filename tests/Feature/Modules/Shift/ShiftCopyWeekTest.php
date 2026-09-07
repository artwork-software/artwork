<?php

namespace Tests\Feature\Modules\Shift;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Shift\Models\GlobalQualification;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftGroup;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Models\ShiftWorker;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Activitylog\Models\Activity;
use Tests\Feature\FeatureTestCase;

/**
 * Block 5B „Woche kopieren": Schichten einer Quell-KW werden mit Wochenversatz (Wochentag bleibt,
 * über Mitternacht bleibt, Jahreswechsel KW 52 → KW 1) in Ziel-KWs neu angelegt — ohne Personen,
 * nicht festgeschrieben, ohne Workflow-/Serien-/Termin-Bezug; belegte Zielzeiten werden übersprungen.
 */
final class ShiftCopyWeekTest extends FeatureTestCase
{
    private Room $room;
    private Craft $craft;

    protected function setUp(): void
    {
        parent::setUp();

        $this->room = Room::factory()->create();
        $this->craft = Craft::factory()->create();
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function makeShift(array $overrides = []): Shift
    {
        return Shift::factory()->create(array_merge([
            'event_id' => null,
            'room_id' => $this->room->id,
            'craft_id' => $this->craft->id,
            'start_date' => '2026-09-09', // Mittwoch, KW 37/2026
            'end_date' => '2026-09-09',
            'start' => '08:00:00',
            'end' => '16:00:00',
            'break_minutes' => 30,
            'description' => 'Aufbau Bühne',
            'is_committed' => false,
        ], $overrides));
    }

    /**
     * @param array<int, array{week:int, year:int}> $targets
     * @param array<string, mixed> $extra
     * @return \Illuminate\Testing\TestResponse
     */
    private function copyWeek(array $targets, array $extra = [])
    {
        return $this->postJson(route('shifts.copy-week'), array_merge([
            'source_week' => 37,
            'source_year' => 2026,
            'targets' => $targets,
        ], $extra));
    }

    #[Test]
    public function copies_shifts_with_week_offset_and_resets_people_and_flags(): void
    {
        $user = $this->actingAsAdmin();
        $qualification = ShiftQualification::factory()->create();
        $globalQualification = GlobalQualification::factory()->create();
        $group = ShiftGroup::factory()->create();

        $source = $this->makeShift([
            'is_committed' => true,
            'in_workflow' => true,
            'shift_uuid' => (string) Str::uuid(),
            'shift_group_id' => $group->id,
        ]);
        $source->shiftsQualifications()->create([
            'shift_qualification_id' => $qualification->id,
            'value' => 3,
            'overbooked_value' => 2,
        ]);
        $source->globalQualifications()->attach($globalQualification->id, ['quantity' => 2]);
        $source->users()->attach(User::factory()->create()->id, [
            'shift_qualification_id' => $qualification->id,
        ]);

        $response = $this->copyWeek([['week' => 38, 'year' => 2026]])
            ->assertSuccessful()
            ->assertJsonPath('targets.0.week', 38)
            ->assertJsonPath('targets.0.created', 1)
            ->assertJsonPath('targets.0.skipped', 0);

        $this->assertStringContainsString('38', (string) $response->json('summary'));
        $this->assertSame(2, Shift::query()->count());

        $copy = Shift::query()->whereKeyNot($source->id)->firstOrFail();
        // Wochentag bleibt: Mittwoch KW 37 → Mittwoch KW 38
        $this->assertSame('2026-09-16', $copy->start_date->format('Y-m-d'));
        $this->assertSame('2026-09-16', $copy->end_date->format('Y-m-d'));
        $this->assertSame('08:00', $copy->start);
        $this->assertSame('16:00', $copy->end);
        $this->assertSame(30, (int) $copy->break_minutes);
        $this->assertSame('Aufbau Bühne', $copy->description);
        $this->assertSame($this->room->id, (int) $copy->room_id);
        $this->assertSame($this->craft->id, (int) $copy->craft_id);
        $this->assertSame($group->id, (int) $copy->shift_group_id);

        // Flags/Bezüge zurückgesetzt
        $this->assertFalse($copy->is_committed);
        $this->assertFalse((bool) $copy->in_workflow);
        $this->assertNull($copy->current_request_id);
        $this->assertNull($copy->shift_uuid);
        $this->assertNull($copy->event_id);

        // Bedarf kopiert, Überbuchung nicht; keine Personen
        $copyQualification = $copy->shiftsQualifications()->firstOrFail();
        $this->assertSame($qualification->id, (int) $copyQualification->shift_qualification_id);
        $this->assertSame(3, (int) $copyQualification->value);
        $this->assertSame(0, (int) $copyQualification->overbooked_value);
        $this->assertSame(2, (int) $copy->globalQualifications()->first()->pivot->quantity);
        $this->assertSame(0, ShiftWorker::query()->where('shift_id', $copy->id)->count());
        $this->assertSame(1, ShiftWorker::query()->where('shift_id', $source->id)->count());

        // Activity-Log wie bei einer Anlage, mit Causer
        $this->assertTrue(
            Activity::query()
                ->where('subject_type', Shift::class)
                ->where('subject_id', $copy->id)
                ->where('event', 'created')
                ->where('causer_id', $user->id)
                ->exists()
        );

        // Flash für den globalen Toast nach dem Plan-Reload
        $response->assertSessionHas('success');
    }

    #[Test]
    public function keeps_shifts_over_midnight_over_midnight(): void
    {
        $this->actingAsAdmin();
        // Samstag 22:00 → Sonntag 04:00
        $this->makeShift([
            'start_date' => '2026-09-12',
            'end_date' => '2026-09-13',
            'start' => '22:00:00',
            'end' => '04:00:00',
        ]);

        $this->copyWeek([['week' => 38, 'year' => 2026]])->assertSuccessful();

        $copy = Shift::query()->where('start_date', '2026-09-19')->firstOrFail();
        $this->assertSame('2026-09-20', $copy->end_date->format('Y-m-d'));
        $this->assertSame('22:00', $copy->start);
        $this->assertSame('04:00', $copy->end);
    }

    #[Test]
    public function copies_week_52_into_week_1_of_the_next_year(): void
    {
        $this->actingAsAdmin();
        // Mittwoch KW 52/2026 = 23.12.2026 → Mittwoch KW 1/2027 = 06.01.2027
        $this->makeShift(['start_date' => '2026-12-23', 'end_date' => '2026-12-23']);

        $this->postJson(route('shifts.copy-week'), [
            'source_week' => 52,
            'source_year' => 2026,
            'targets' => [['week' => 1, 'year' => 2027]],
        ])
            ->assertSuccessful()
            ->assertJsonPath('targets.0.created', 1);

        $this->assertTrue(Shift::query()->whereDate('start_date', '2027-01-06')->exists());
    }

    #[Test]
    public function skips_source_shifts_whose_target_time_is_already_occupied(): void
    {
        $this->actingAsAdmin();
        $this->makeShift(); // Mi 08–16
        $this->makeShift(['start' => '17:00:00', 'end' => '23:00:00']); // Mi 17–23
        // Zielwoche: gleicher Raum, gleiches Gewerk, gleiche Zeit → belegt
        $this->makeShift(['start_date' => '2026-09-16', 'end_date' => '2026-09-16']);

        $response = $this->copyWeek([['week' => 38, 'year' => 2026]])
            ->assertSuccessful()
            ->assertJsonPath('targets.0.created', 1)
            ->assertJsonPath('targets.0.skipped', 1)
            ->assertJsonPath('targets.0.skipped_shifts.0.date', '16.09.2026')
            ->assertJsonPath('targets.0.skipped_shifts.0.start', '08:00')
            ->assertJsonPath('targets.0.skipped_shifts.0.end', '16:00');

        $this->assertSame($this->room->name, $response->json('targets.0.skipped_shifts.0.room'));
        $this->assertSame(1, Shift::query()->whereDate('start_date', '2026-09-16')->where('start', '08:00:00')->count());
        $this->assertSame(1, Shift::query()->whereDate('start_date', '2026-09-16')->where('start', '17:00:00')->count());
    }

    #[Test]
    public function different_craft_or_room_at_same_time_is_not_treated_as_occupied(): void
    {
        $this->actingAsAdmin();
        $this->makeShift();
        // Zielwoche: gleiche Zeit, aber anderes Gewerk → keine Belegung
        $this->makeShift([
            'start_date' => '2026-09-16',
            'end_date' => '2026-09-16',
            'craft_id' => Craft::factory()->create()->id,
        ]);

        $this->copyWeek([['week' => 38, 'year' => 2026]])
            ->assertSuccessful()
            ->assertJsonPath('targets.0.created', 1)
            ->assertJsonPath('targets.0.skipped', 0);
    }

    #[Test]
    public function craft_filter_limits_copied_shifts(): void
    {
        $this->actingAsAdmin();
        $otherCraft = Craft::factory()->create();
        $this->makeShift();
        $this->makeShift(['craft_id' => $otherCraft->id, 'start' => '10:00:00', 'end' => '18:00:00']);

        $this->copyWeek([['week' => 38, 'year' => 2026]], ['craft_ids' => [$this->craft->id]])
            ->assertSuccessful()
            ->assertJsonPath('source.count', 1)
            ->assertJsonPath('targets.0.created', 1);

        $copies = Shift::query()->whereDate('start_date', '2026-09-16')->get();
        $this->assertCount(1, $copies);
        $this->assertSame($this->craft->id, (int) $copies->first()->craft_id);
    }

    #[Test]
    public function deleted_source_shifts_are_ignored(): void
    {
        $this->actingAsAdmin();
        $this->makeShift();
        $this->makeShift(['start' => '10:00:00', 'end' => '18:00:00'])->delete();

        $this->copyWeek([['week' => 38, 'year' => 2026]])
            ->assertSuccessful()
            ->assertJsonPath('source.count', 1)
            ->assertJsonPath('targets.0.created', 1);
    }

    #[Test]
    public function rejects_a_source_week_without_shifts(): void
    {
        $this->actingAsAdmin();

        $this->copyWeek([['week' => 38, 'year' => 2026]])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['source_week']);

        $this->assertSame(0, Shift::query()->count());
    }

    #[Test]
    public function rejects_targets_equal_to_source_or_more_than_eight(): void
    {
        $this->actingAsAdmin();
        $this->makeShift();

        $this->copyWeek([['week' => 37, 'year' => 2026]])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['targets.0']);

        $tooMany = [];
        for ($week = 38; $week <= 46; $week++) {
            $tooMany[] = ['week' => $week, 'year' => 2026];
        }
        $this->copyWeek($tooMany)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['targets']);

        $this->assertSame(1, Shift::query()->count());
    }

    #[Test]
    public function requires_shift_planning_permission(): void
    {
        $this->makeShift();
        $this->actingAs(User::factory()->create());

        $this->copyWeek([['week' => 38, 'year' => 2026]])->assertForbidden();
        $this->getJson(route('shifts.copy-week.preview', ['source_week' => 37, 'source_year' => 2026]))
            ->assertForbidden();

        $this->assertSame(1, Shift::query()->count());
    }

    #[Test]
    public function planner_permission_is_sufficient(): void
    {
        $this->makeShift();
        $this->actingAsUserWith('can plan shifts');

        $this->copyWeek([['week' => 38, 'year' => 2026]])
            ->assertSuccessful()
            ->assertJsonPath('targets.0.created', 1);
    }

    #[Test]
    public function copies_into_multiple_target_weeks(): void
    {
        $this->actingAsAdmin();
        $this->makeShift();
        $this->makeShift(['start' => '17:00:00', 'end' => '23:00:00']);

        $response = $this->copyWeek([
            ['week' => 38, 'year' => 2026],
            ['week' => 40, 'year' => 2026],
        ])
            ->assertSuccessful()
            ->assertJsonCount(2, 'targets')
            ->assertJsonPath('targets.0.week', 38)
            ->assertJsonPath('targets.0.created', 2)
            ->assertJsonPath('targets.1.week', 40)
            ->assertJsonPath('targets.1.created', 2);

        $this->assertSame(2, Shift::query()->whereDate('start_date', '2026-09-16')->count());
        $this->assertSame(2, Shift::query()->whereDate('start_date', '2026-09-30')->count());
        $this->assertSame(0, Shift::query()->whereDate('start_date', '2026-09-23')->count());

        $summary = (string) $response->json('summary');
        $this->assertStringContainsString('38', $summary);
        $this->assertStringContainsString('40', $summary);
    }

    #[Test]
    public function preview_counts_source_shifts_of_the_week(): void
    {
        $this->actingAsAdmin();
        $otherCraft = Craft::factory()->create();
        $this->makeShift();
        $this->makeShift(['craft_id' => $otherCraft->id, 'start' => '10:00:00', 'end' => '18:00:00']);
        // Nachbarwoche zählt nicht mit
        $this->makeShift(['start_date' => '2026-09-14', 'end_date' => '2026-09-14']);

        $this->getJson(route('shifts.copy-week.preview', ['source_week' => 37, 'source_year' => 2026]))
            ->assertSuccessful()
            ->assertJsonPath('count', 2)
            ->assertJsonPath('start', '07.09.2026')
            ->assertJsonPath('end', '13.09.2026');

        $this->getJson(route('shifts.copy-week.preview', [
            'source_week' => 37,
            'source_year' => 2026,
            'craft_ids' => [$otherCraft->id],
        ]))
            ->assertSuccessful()
            ->assertJsonPath('count', 1);
    }
}
