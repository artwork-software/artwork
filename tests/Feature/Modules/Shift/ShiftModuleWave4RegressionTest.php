<?php

namespace Tests\Feature\Modules\Shift;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Shift\Models\CommittedShiftChange;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftPlanRequest;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Services\ShiftWorkerService;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Welle-4-Regression: CommittedShiftChange-Guard (B23), Carbon-Vorzeichen (B24),
 * Schichtdatum in Labels (B25), FK SET NULL (B27), Validierung (B30),
 * bulkDuplicate ohne Workflow-Identität (B34), Doppel-Request-Schutz (B22).
 */
final class ShiftModuleWave4RegressionTest extends FeatureTestCase
{
    #[Test]
    public function plain_planning_assignment_creates_no_committed_shift_change(): void
    {
        $this->actingAsAdmin();
        $shift = Shift::factory()->create(['is_committed' => false, 'in_workflow' => false]);
        $qualification = ShiftQualification::factory()->create();
        $shift->shiftsQualifications()->create(['shift_qualification_id' => $qualification->id, 'value' => 1]);

        app(ShiftWorkerService::class)->assignToShift($shift, User::factory()->create(), $qualification->id, 'TST');

        $this->assertDatabaseMissing('committed_shift_changes', ['shift_id' => $shift->id]);
    }

    #[Test]
    public function committed_shift_assignment_still_creates_committed_shift_change(): void
    {
        $this->actingAsAdmin();
        $shift = Shift::factory()->create(['is_committed' => true]);
        $qualification = ShiftQualification::factory()->create();
        $shift->shiftsQualifications()->create(['shift_qualification_id' => $qualification->id, 'value' => 1]);

        $worker = User::factory()->create();
        app(ShiftWorkerService::class)->assignToShift($shift, $worker, $qualification->id, 'TST');

        $this->assertDatabaseHas('committed_shift_changes', [
            'shift_id' => $shift->id,
            'affected_user_id' => $worker->id,
        ]);
    }

    #[Test]
    public function over_midnight_shift_without_break_is_an_infringement(): void
    {
        // 22:00–06:00 = 8h ohne Pause → Verstoß; vorher war der Carbon-3-Diff
        // negativ und der Check feuerte nie.
        $shift = Shift::factory()->create([
            'start_date' => '2026-05-06',
            'end_date' => '2026-05-07',
            'start' => '22:00:00',
            'end' => '06:00:00',
            'break_minutes' => 0,
        ]);

        $this->assertTrue($shift->infringement);
    }

    #[Test]
    public function exactly_nine_hours_with_thirty_minute_break_is_no_infringement(): void
    {
        // 540-min-Grenzfall: exakt 9h braucht nur 30 min (45 min erst ab 9:01);
        // vorher war 540 in keinem Intervall (> 360 && < 540 || > 540) und wurde nie geprüft.
        $shift = Shift::factory()->create([
            'start_date' => '2026-05-06',
            'end_date' => '2026-05-06',
            'start' => '08:00:00',
            'end' => '17:00:00',
            'break_minutes' => 30,
        ]);

        $this->assertFalse($shift->infringement);

        $shift->break_minutes = 15;
        $this->assertTrue($shift->infringement);
    }

    #[Test]
    public function one_minute_over_nine_hours_requires_forty_five_minute_break(): void
    {
        $shift = Shift::factory()->create([
            'start_date' => '2026-05-06',
            'end_date' => '2026-05-06',
            'start' => '08:00:00',
            'end' => '17:01:00',
            'break_minutes' => 30,
        ]);

        $this->assertTrue($shift->infringement);

        $shift->break_minutes = 45;
        $this->assertFalse($shift->infringement);
    }

    #[Test]
    public function exactly_six_hours_requires_no_break(): void
    {
        $shift = Shift::factory()->create([
            'start_date' => '2026-05-06',
            'end_date' => '2026-05-06',
            'start' => '08:00:00',
            'end' => '14:00:00',
            'break_minutes' => 0,
        ]);

        $this->assertFalse($shift->infringement);

        $shift->end = '14:01:00';
        $this->assertTrue($shift->infringement);
    }

    #[Test]
    public function time_span_label_uses_shift_date_not_today(): void
    {
        $shift = Shift::factory()->create([
            'start_date' => '2026-05-06',
            'end_date' => '2026-05-06',
            'start' => '09:00:00',
            'end' => '17:00:00',
        ]);

        $this->assertSame('06.05.2026 09:00 - 17:00', $shift->time_span_label);
        $this->assertStringNotContainsString(now()->format('d.m.Y'), $shift->time_span_label);
    }

    #[Test]
    public function force_deleting_committing_user_keeps_their_committed_shifts(): void
    {
        $committingUser = User::factory()->create();
        $shift = Shift::factory()->create([
            'is_committed' => true,
            'committing_user_id' => $committingUser->id,
        ]);

        $committingUser->forceDelete();

        $this->assertDatabaseHas('shifts', [
            'id' => $shift->id,
            'committing_user_id' => null,
        ]);
    }

    #[Test]
    public function store_shift_without_event_requires_qualifications_field(): void
    {
        $this->actingAsAdmin();
        $craft = Craft::factory()->create();
        $shiftCountBefore = Shift::count();

        $this->postJson(route('event.shift.store.without.event'), [
            'craft_id' => $craft->id,
            'day' => '2026-05-06',
            'start' => '09:00',
            'end' => '17:00',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['shiftsQualifications']);

        // Vorher: TypeError NACH dem Speichern → halbfertige Schicht blieb zurück
        $this->assertSame($shiftCountBefore, Shift::count());
    }

    #[Test]
    public function bulk_duplicate_does_not_copy_series_and_workflow_identity(): void
    {
        $this->actingAsAdmin();
        $request = ShiftPlanRequest::create([
            'craft_id' => Craft::factory()->create()->id,
            'week_number' => 19,
            'year' => 2026,
            'status' => 'pending',
            'requested_by_user_id' => $this->adminUser()->id,
            'requested_at' => now(),
        ]);
        $shift = Shift::factory()->create([
            'shift_uuid' => (string) \Illuminate\Support\Str::uuid(),
            'in_workflow' => true,
            'current_request_id' => $request->id,
            'is_committed' => true,
            'committing_user_id' => $this->adminUser()->id,
            'workflow_rejection_reason' => 'abgelehnt',
        ]);

        $this->post(route('shifts.multi.duplicate'), ['shift_ids' => [$shift->id]]);

        $duplicate = Shift::where('id', '!=', $shift->id)->latest('id')->first();
        $this->assertNotNull($duplicate);
        $this->assertNull($duplicate->shift_uuid);
        $this->assertFalse((bool) $duplicate->in_workflow);
        $this->assertNull($duplicate->current_request_id);
        $this->assertNull($duplicate->committing_user_id);
        $this->assertNull($duplicate->workflow_rejection_reason);
        $this->assertFalse((bool) $duplicate->is_committed);
    }

    #[Test]
    public function double_request_submission_reuses_pending_request(): void
    {
        $this->actingAsAdmin();
        $craft = Craft::factory()->create();
        Shift::factory()->create([
            'craft_id' => $craft->id,
            'start_date' => '2026-05-06',
            'end_date' => '2026-05-06',
            'in_workflow' => false,
            'current_request_id' => null,
        ]);

        $payload = ['craft_id' => $craft->id, 'week_number' => 19, 'year' => 2026];
        $this->post(route('commit-shift-workflow-request.store'), $payload);
        $this->post(route('commit-shift-workflow-request.store'), $payload);

        $this->assertSame(
            1,
            ShiftPlanRequest::where('craft_id', $craft->id)
                ->where('week_number', 19)
                ->where('year', 2026)
                ->where('status', 'pending')
                ->count()
        );
    }
}
