<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class CompensationWeekScheduleTest extends FeatureTestCase
{
    #[Test]
    public function week_schedule_contains_shifts_from_unified_pivot(): void
    {
        $this->actingAsUserWith('can plan shifts');

        $worker = User::factory()->create();
        $shift = Shift::factory()->create([
            'start_date' => '2026-06-10',
            'end_date' => '2026-06-10',
            'start' => '08:00:00',
            'end' => '14:00:00',
        ]);
        // Assignments live in the unified shift_workers pivot (employable morph).
        $qualification = ShiftQualification::query()->firstOrCreate(
            ['name' => 'Mitarbeiter'],
            ['icon' => 'IconUser', 'available' => true]
        );
        $worker->shifts()->attach($shift->id, [
            'shift_qualification_id' => $qualification->id,
            'start_date' => '2026-06-10',
            'end_date' => '2026-06-10',
            'start_time' => '08:00',
            'end_time' => '14:00',
        ]);

        $response = $this->getJson(
            route('compensation-day-offs.week-schedule', ['user' => $worker->id]) . '?date=2026-06-12'
        );

        $response->assertOk();
        $days = collect($response->json('days'))->keyBy('date');

        $wednesday = $days->get('2026-06-10');
        $this->assertNotNull($wednesday);
        $this->assertFalse($wednesday['is_free']);
        $this->assertSame([['start' => '08:00', 'end' => '14:00']], $wednesday['shifts']);

        $friday = $days->get('2026-06-12');
        $this->assertTrue($friday['is_free']);
        $this->assertSame([], $friday['shifts']);
    }
}
