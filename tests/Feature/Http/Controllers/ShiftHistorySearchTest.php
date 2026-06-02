<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Shift\Models\Shift;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Activitylog\Models\Activity;
use Tests\Feature\FeatureTestCase;

final class ShiftHistorySearchTest extends FeatureTestCase
{
    private function makeShift(): Shift
    {
        $craft = Craft::factory()->create();

        return Shift::factory()->create([
            'craft_id' => $craft->id,
            'start_date' => '2026-05-06',
            'end_date' => '2026-05-06',
            'start' => '09:00:00',
            'end' => '17:00:00',
            'in_workflow' => false,
            'current_request_id' => null,
        ]);
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
}
