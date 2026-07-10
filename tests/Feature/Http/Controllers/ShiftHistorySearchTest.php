<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Shift\Models\Shift;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Activitylog\Models\Activity;
use Tests\Feature\FeatureTestCase;

final class ShiftHistorySearchTest extends FeatureTestCase
{
    private function makeShift(array $attributes = []): Shift
    {
        return Shift::factory()->create(array_merge([
            'craft_id' => Craft::factory()->create()->id,
            'start_date' => '2026-05-06',
            'end_date' => '2026-05-06',
            'start' => '09:00:00',
            'end' => '17:00:00',
            'in_workflow' => false,
            'current_request_id' => null,
        ], $attributes));
    }

    private function logActivity(Shift $shift, string $description, array $properties = []): Activity
    {
        return activity('shift')
            ->performedOn($shift)
            ->withProperties($properties)
            ->log($description);
    }

    #[Test]
    public function search_finds_matching_entry_even_when_paginated_away(): void
    {
        $this->actingAsAdmin();
        $shift = $this->makeShift();

        // Lots of noise entries (would fill the first pages on their own)
        for ($i = 0; $i < 12; $i++) {
            $this->logActivity($shift, 'shift updated noise ' . $i);
        }

        // The single entry we want to find – contains the worker name in the
        // translation placeholders, like a real "assigned to shift" entry.
        $this->logActivity($shift, 'User assigned to shift', [
            'translation_key' => '{0} was assigned to shift as {1} for {2} ({3})',
            'translation_key_placeholder_values' => ['Zaphod Beeblebrox', 'Tech', 'Stage', 'ST'],
        ]);

        $response = $this->getJson(route('shift.history.index', [
            'craftId' => $shift->craft_id,
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-31',
            'per_page' => 5,
            'page' => 1,
            'search' => 'Zaphod',
        ]));

        $response->assertOk();

        // Server-side filtering: the only matching row is returned on page 1,
        // not buried behind 12 newer noise entries.
        $response->assertJsonPath('logs.meta.total', 1);
        $this->assertCount(1, $response->json('logs.data'));
    }

    #[Test]
    public function search_returns_nothing_for_unknown_name(): void
    {
        $this->actingAsAdmin();
        $shift = $this->makeShift();

        $this->logActivity($shift, 'User assigned to shift', [
            'translation_key_placeholder_values' => ['Arthur Dent'],
        ]);

        $response = $this->getJson(route('shift.history.index', [
            'craftId' => $shift->craft_id,
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-31',
            'search' => 'Trillian',
        ]));

        $response->assertOk();
        $response->assertJsonPath('logs.meta.total', 0);
    }

    #[Test]
    public function search_is_case_insensitive_and_matches_partial_names(): void
    {
        $this->actingAsAdmin();
        $shift = $this->makeShift();

        $this->logActivity($shift, 'User assigned to shift', [
            'translation_key' => '{0} was assigned to shift as {1} for {2} ({3})',
            'translation_key_placeholder_values' => ['Jannik Müller', 'Tech', 'Stage', 'ST'],
        ]);

        // Lower-case, partial first name must still find "Jannik Müller".
        $response = $this->getJson(route('shift.history.index', [
            'craftId' => $shift->craft_id,
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-31',
            'search' => 'jannik',
        ]));

        $response->assertOk();
        $response->assertJsonPath('logs.meta.total', 1);
        $this->assertCount(1, $response->json('logs.data'));
    }

    #[Test]
    public function shift_day_sort_orders_by_shift_start_date_not_change_date(): void
    {
        $this->actingAsAdmin();
        $craft = Craft::factory()->create();

        $earlyShift = Shift::factory()->create([
            'craft_id' => $craft->id,
            'start_date' => '2026-05-05', 'end_date' => '2026-05-05',
            'start' => '09:00:00', 'end' => '17:00:00',
            'in_workflow' => false, 'current_request_id' => null,
        ]);
        $lateShift = Shift::factory()->create([
            'craft_id' => $craft->id,
            'start_date' => '2026-05-20', 'end_date' => '2026-05-20',
            'start' => '09:00:00', 'end' => '17:00:00',
            'in_workflow' => false, 'current_request_id' => null,
        ]);

        // Change the LATE shift first, then the EARLY shift (newest created_at = early).
        // Under created_at sort the early entry would be on top; under shift_day sort the
        // late shift's entry must be on top (later shift day first).
        $this->logActivity($lateShift, 'late shift change');
        $this->logActivity($earlyShift, 'early shift change');

        $response = $this->getJson(route('shift.history.index', [
            'craftId' => $craft->id,
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-31',
            'sort' => 'shift_day',
        ]));

        $response->assertOk();
        $data = $response->json('logs.data');
        $this->assertNotEmpty($data);
        $this->assertSame($lateShift->id, (int) $data[0]['subject_id']);
    }

    #[Test]
    public function search_with_shift_day_sort_does_not_break_on_joined_columns(): void
    {
        // Regression: mit sort=shift_day wird shifts gejoint; shifts hat ebenfalls eine
        // Spalte "description" → unqualifiziertes LOWER(description) war ambiguous (1052).
        $this->actingAsAdmin();
        $shift = $this->makeShift();

        $this->logActivity($shift, 'User assigned to shift', [
            'translation_key' => '{0} was assigned to shift as {1} for {2} ({3})',
            'translation_key_placeholder_values' => ['Ehlers', 'Tech', 'Stage', 'ST'],
        ]);

        $response = $this->getJson(route('shift.history.index', [
            'craftId' => $shift->craft_id,
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-31',
            'search' => 'ehlers',
            'sort' => 'shift_day',
        ]));

        $response->assertOk();
        $response->assertJsonPath('logs.meta.total', 1);
    }

    #[Test]
    public function without_search_all_entries_are_returned(): void
    {
        $this->actingAsAdmin();
        $shift = $this->makeShift();

        for ($i = 0; $i < 3; $i++) {
            $this->logActivity($shift, 'shift updated ' . $i);
        }

        $response = $this->getJson(route('shift.history.index', [
            'craftId' => $shift->craft_id,
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-31',
        ]));

        $response->assertOk();
        // At least our 3 entries are returned unfiltered (the shift's own creation may add more).
        $this->assertGreaterThanOrEqual(3, (int) $response->json('logs.meta.total'));
    }

    #[Test]
    public function firstPageReturnsTheMinimalShiftContract(): void
    {
        $this->actingAsAdmin();
        $shift = $this->makeShift();

        $response = $this->getJson(route('shift.history.index', [
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-31',
        ]));

        $response->assertOk();
        $payload = $response->json('shifts.0');

        $this->assertSame([
            'id',
            'craft_id',
            'start_date',
            'end_date',
            'start',
            'end',
            'description',
            'is_committed',
            'in_workflow',
            'deleted_at',
            'room',
            'project',
            'craft',
        ], array_keys($payload));
        $this->assertSame($shift->id, $payload['id']);
        $this->assertSame('06. May 2026', $payload['start_date']);
        $this->assertSame('09:00', $payload['start']);
        $this->assertSame(
            $shift->craft()->firstOrFail()->only(['id', 'name', 'abbreviation']),
            $payload['craft']
        );
    }

    #[Test]
    public function exactShiftFilterIsAppliedBeforePagination(): void
    {
        $this->actingAsAdmin();
        $craft = Craft::factory()->create();
        $targetShift = $this->makeShift(['craft_id' => $craft->id]);
        for ($i = 0; $i < 3; $i++) {
            $this->logActivity($targetShift, 'target shift history ' . $i);
        }

        $otherShift = $this->makeShift(['craft_id' => $craft->id]);
        for ($i = 0; $i < 5; $i++) {
            $this->logActivity($otherShift, 'newer noise ' . $i);
        }

        activity('shift')
            ->event('committed_bulk')
            ->withProperties([
                'shift_ids' => [$otherShift->id],
                'craft_ids' => [$craft->id],
                'commit_summary' => ['start_date' => '2026-05-06', 'end_date' => '2026-05-06'],
            ])
            ->log('other shift bulk commitment');
        activity('shift')
            ->event('committed_bulk')
            ->withProperties([
                'shift_ids' => [$targetShift->id],
                'craft_ids' => [$craft->id],
                'commit_summary' => ['start_date' => '2026-05-06', 'end_date' => '2026-05-06'],
            ])
            ->log('target shift bulk commitment');

        $query = [
            'craftId' => $craft->id,
            'shiftId' => $targetShift->id,
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-31',
            'per_page' => 2,
        ];
        $expectedTotal = Activity::query()
            ->where('log_name', 'shift')
            ->where(function ($query) use ($targetShift): void {
                $query->where('subject_id', $targetShift->id)
                    ->orWhereJsonContains('properties->shift_ids', $targetShift->id);
            })
            ->count();

        $firstPage = $this->getJson(route('shift.history.index', $query));
        $secondPage = $this->getJson(route('shift.history.index', [...$query, 'page' => 2]));

        $firstPage->assertOk()->assertJsonPath('logs.meta.total', $expectedTotal);
        $secondPage->assertOk()->assertJsonPath('logs.meta.total', $expectedTotal)->assertJsonMissingPath('shifts');
        $this->assertEqualsCanonicalizing(
            [$targetShift->id, $otherShift->id],
            collect($firstPage->json('shifts'))->pluck('id')->all()
        );

        $logs = collect([...$firstPage->json('logs.data'), ...$secondPage->json('logs.data')]);
        $this->assertContains('target shift bulk commitment', $logs->pluck('description'));
        $this->assertNotContains('other shift bulk commitment', $logs->pluck('description'));
        $this->assertTrue($logs->every(
            fn (array $log): bool => (int) ($log['subject_id'] ?? 0) === $targetShift->id
                || in_array($targetShift->id, data_get($log, 'properties.shift_ids', []), true)
        ));
    }

    #[Test]
    public function exactShiftFilterCannotBypassTheCraftFilter(): void
    {
        $this->actingAsAdmin();
        $shift = $this->makeShift();

        $response = $this->getJson(route('shift.history.index', [
            'craftId' => Craft::factory()->create()->id,
            'shiftId' => $shift->id,
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-31',
        ]));

        $response->assertOk()
            ->assertJsonPath('logs.meta.total', 0)
            ->assertJsonCount(0, 'shifts');
    }
}
