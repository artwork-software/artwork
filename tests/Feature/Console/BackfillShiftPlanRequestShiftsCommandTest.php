<?php

namespace Tests\Feature\Console;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Shift\Console\Commands\BackfillShiftPlanRequestShiftsCommand;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftPlanRequest;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\Feature\FeatureTestCase;

final class BackfillShiftPlanRequestShiftsCommandTest extends FeatureTestCase
{
    /**
     * Run the command in isolation (without booting the full console kernel, which would
     * eagerly instantiate every command – one of which is incompatible with Mail::fake()).
     *
     * @param array<string, mixed> $params
     */
    private function runBackfill(array $params = []): int
    {
        $command = $this->app->make(BackfillShiftPlanRequestShiftsCommand::class);
        $command->setLaravel($this->app);

        $tester = new CommandTester($command);
        $tester->execute($params);

        return $tester->getStatusCode();
    }

    private function freeShiftInWeek19(Craft $craft): Shift
    {
        // 2026-05-06 liegt in ISO-KW 19
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

    #[Test]
    public function backfill_attaches_missing_free_shifts_to_pending_request(): void
    {
        $craft = Craft::factory()->create();
        // A pending request that (due to the old bug) has no shifts attached yet.
        $request = ShiftPlanRequest::factory()->create([
            'craft_id' => $craft->id,
            'week_number' => 19,
            'year' => 2026,
            'status' => 'pending',
        ]);

        $shift = $this->freeShiftInWeek19($craft);

        $this->assertFalse($request->requestedShifts()->where('shift_id', $shift->id)->exists());

        $this->assertSame(0, $this->runBackfill(['--no-broadcast' => true]));

        $shift->refresh();
        $this->assertSame($request->id, $shift->current_request_id);
        $this->assertTrue((bool) $shift->in_workflow);
        $this->assertTrue($request->requestedShifts()->where('shift_id', $shift->id)->exists());
    }

    #[Test]
    public function dry_run_reports_but_does_not_change_anything(): void
    {
        $craft = Craft::factory()->create();
        $request = ShiftPlanRequest::factory()->create([
            'craft_id' => $craft->id,
            'week_number' => 19,
            'year' => 2026,
            'status' => 'pending',
        ]);
        $shift = $this->freeShiftInWeek19($craft);

        $this->assertSame(0, $this->runBackfill(['--dry-run' => true]));

        $shift->refresh();
        $this->assertNull($shift->current_request_id);
        $this->assertFalse((bool) $shift->in_workflow);
        $this->assertFalse($request->requestedShifts()->where('shift_id', $shift->id)->exists());
    }

    #[Test]
    public function approved_requests_are_left_untouched_by_default(): void
    {
        $craft = Craft::factory()->create();
        $request = ShiftPlanRequest::factory()->create([
            'craft_id' => $craft->id,
            'week_number' => 19,
            'year' => 2026,
            'status' => 'approved',
        ]);
        $shift = $this->freeShiftInWeek19($craft);

        $this->assertSame(0, $this->runBackfill(['--no-broadcast' => true]));

        // Default only touches pending requests, so the free shift stays free.
        $shift->refresh();
        $this->assertNull($shift->current_request_id);
        $this->assertFalse($request->requestedShifts()->where('shift_id', $shift->id)->exists());
    }

    #[Test]
    public function once_runs_a_single_time_and_is_skipped_afterwards(): void
    {
        $craft = Craft::factory()->create();
        $request = ShiftPlanRequest::factory()->create([
            'craft_id' => $craft->id,
            'week_number' => 19,
            'year' => 2026,
            'status' => 'pending',
        ]);
        $shiftA = $this->freeShiftInWeek19($craft);

        // First run with --once: attaches and records the marker.
        $this->assertSame(0, $this->runBackfill(['--once' => true, '--no-broadcast' => true]));
        $shiftA->refresh();
        $this->assertSame($request->id, $shiftA->current_request_id);
        $this->assertDatabaseHas('one_time_tasks', ['key' => 'shift-plan-requests:backfill']);

        // A new free shift appears after the one-time repair already ran.
        $shiftB = $this->freeShiftInWeek19($craft);

        // Second --once run must be a no-op (skipped), so the new shift stays free.
        $this->assertSame(0, $this->runBackfill(['--once' => true, '--no-broadcast' => true]));
        $shiftB->refresh();
        $this->assertNull($shiftB->current_request_id);
        $this->assertFalse((bool) $shiftB->in_workflow);
    }
}
