<?php

namespace Tests\Unit\Modules\Shift\Services;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Shift\Models\CommittedShiftChange;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Services\ShiftChangeRecorder;
use Artwork\Modules\Shift\Models\ShiftPlanRequest;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Models\ShiftRuleViolation;
use Artwork\Modules\Shift\Models\ShiftsQualifications;
use Artwork\Modules\Shift\Services\ShiftWeekStatusService;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Wochenstatus je Gewerk × KW (Seite „Wochenstatus"): Status-Ableitung, Zählungen,
 * Frist-Zustände und konstantes Query-Budget.
 */
final class ShiftWeekStatusServiceTest extends TestCase
{
    /** Montag der ISO-KW 41/2026 */
    private const MONDAY = '2026-10-05';

    private const WEEK_KEY = '2026-W41';

    private ShiftWeekStatusService $service;

    private Craft $craft;

    private User $requester;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ShiftWeekStatusService::class);
        $this->craft = Craft::factory()->create(['commit_request_deadline_days' => null]);
        $this->requester = User::factory()->create();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function makeShift(array $attributes = [], ?Craft $craft = null, string $date = self::MONDAY): Shift
    {
        // Ohne Recorder: sonst erzeugt eine festgeschriebene Schicht beim Anlegen selbst eine Änderung
        return ShiftChangeRecorder::withoutRecording(fn () => Shift::factory()->create(array_merge([
            'craft_id' => ($craft ?? $this->craft)->id,
            'start_date' => $date,
            'end_date' => $date,
            'start' => '10:00',
            'end' => '18:00',
            'is_committed' => false,
            'in_workflow' => false,
        ], $attributes)));
    }

    private function makeRequest(string $status, ?Craft $craft = null, int $week = 41, int $year = 2026): ShiftPlanRequest
    {
        return ShiftPlanRequest::create([
            'craft_id' => ($craft ?? $this->craft)->id,
            'week_number' => $week,
            'year' => $year,
            'status' => $status,
            'requested_by_user_id' => $this->requester->id,
            'requested_at' => now(),
        ]);
    }

    private function makeChange(Shift $shift, bool $acknowledged = false): CommittedShiftChange
    {
        return CommittedShiftChange::create([
            'craft_id' => $shift->craft_id,
            'shift_id' => $shift->id,
            'subject_type' => Shift::class,
            'subject_id' => $shift->id,
            'change_type' => 'updated',
            'field_changes' => ['start' => ['old' => '09:00', 'new' => '10:00']],
            'changed_by_user_id' => $this->requester->id,
            'changed_at' => Carbon::parse(self::MONDAY)->setTime(12, 0),
            'acknowledged_at' => $acknowledged ? now() : null,
            'acknowledged_by_user_id' => $acknowledged ? $this->requester->id : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function cell(?Craft $craft = null, string $weekKey = self::WEEK_KEY, int $weeks = 1, ?Carbon $today = null): array
    {
        $from = Carbon::parse(self::MONDAY);
        $to = $from->copy()->addWeeks($weeks)->subDay();
        $result = $this->service->compute($from, $to, [($craft ?? $this->craft)->id], $today);

        return $result['rows'][($craft ?? $this->craft)->id][$weekKey];
    }

    #[Test]
    public function week_without_shifts_is_none_with_zero_counts(): void
    {
        $cell = $this->cell();

        $this->assertSame('none', $cell['status']);
        $this->assertSame(0, $cell['shifts_total']);
        $this->assertSame(0, $cell['required_slots']);
        $this->assertSame(0, $cell['open_changes']);
        $this->assertSame(0, $cell['open_violations']);
        $this->assertNull($cell['request_id']);
        $this->assertNull($cell['deadline_date']);
        $this->assertNull($cell['deadline_state']);
    }

    #[Test]
    public function uncommitted_shifts_without_request_are_open(): void
    {
        $this->makeShift();
        $this->makeShift([], null, '2026-10-09');

        $cell = $this->cell();

        $this->assertSame('open', $cell['status']);
        $this->assertSame(2, $cell['shifts_total']);
        $this->assertSame(0, $cell['shifts_committed']);
    }

    #[Test]
    public function all_shifts_committed_is_committed(): void
    {
        $this->makeShift(['is_committed' => true]);
        $this->makeShift(['is_committed' => true], null, '2026-10-11');

        $cell = $this->cell();

        $this->assertSame('committed', $cell['status']);
        $this->assertSame(2, $cell['shifts_committed']);
    }

    #[Test]
    public function mixed_commitment_is_partial(): void
    {
        $this->makeShift(['is_committed' => true]);
        $this->makeShift();

        $this->assertSame('partial', $this->cell()['status']);
    }

    #[Test]
    public function pending_request_is_requested_even_when_partially_committed(): void
    {
        $this->makeShift(['is_committed' => true]);
        $this->makeShift(['in_workflow' => true]);
        $request = $this->makeRequest('pending');

        $cell = $this->cell();

        $this->assertSame('requested', $cell['status']);
        $this->assertSame($request->id, $cell['request_id']);
        $this->assertSame('pending', $cell['request_status']);
        $this->assertNotNull($cell['requested_at']);
    }

    #[Test]
    public function committed_wins_over_pending_request(): void
    {
        $this->makeShift(['is_committed' => true]);
        $this->makeRequest('pending');

        $this->assertSame('committed', $this->cell()['status']);
    }

    #[Test]
    public function rejected_latest_request_without_new_request_is_rejected(): void
    {
        $this->makeShift();
        $this->makeRequest('approved');
        $rejected = $this->makeRequest('rejected');

        $cell = $this->cell();

        $this->assertSame('rejected', $cell['status']);
        $this->assertSame($rejected->id, $cell['request_id']);
        $this->assertSame('rejected', $cell['request_status']);
    }

    #[Test]
    public function resubmitted_request_after_rejection_is_requested(): void
    {
        $this->makeShift();
        $this->makeRequest('rejected');
        $pending = $this->makeRequest('pending');

        $cell = $this->cell();

        $this->assertSame('requested', $cell['status']);
        $this->assertSame($pending->id, $cell['request_id']);
    }

    #[Test]
    public function requests_of_other_weeks_or_crafts_do_not_affect_the_cell(): void
    {
        $this->makeShift();
        $this->makeRequest('pending', null, 42);
        $this->makeRequest('pending', Craft::factory()->create());

        $this->assertSame('open', $this->cell()['status']);
    }

    #[Test]
    public function only_unacknowledged_changes_of_the_cells_shifts_are_counted(): void
    {
        $shift = $this->makeShift(['is_committed' => true]);
        $this->makeChange($shift);
        $this->makeChange($shift);
        $this->makeChange($shift, true);

        $otherWeekShift = $this->makeShift(['is_committed' => true], null, '2026-10-12');
        $this->makeChange($otherWeekShift);

        $this->assertSame(2, $this->cell()['open_changes']);
    }

    #[Test]
    public function active_violations_of_scheduled_users_in_the_week_are_counted(): void
    {
        $qualification = ShiftQualification::factory()->create();
        $shift = $this->makeShift();
        $scheduled = User::factory()->create();
        $notScheduled = User::factory()->create();
        $shift->users()->attach($scheduled->id, ['shift_qualification_id' => $qualification->id]);

        ShiftRuleViolation::factory()->create(['user_id' => $scheduled->id, 'violation_date' => '2026-10-07', 'status' => 'active']);
        ShiftRuleViolation::factory()->create(['user_id' => $scheduled->id, 'violation_date' => '2026-10-11', 'status' => 'active']);
        ShiftRuleViolation::factory()->create(['user_id' => $scheduled->id, 'violation_date' => '2026-10-07', 'status' => 'resolved']);
        ShiftRuleViolation::factory()->create(['user_id' => $scheduled->id, 'violation_date' => '2026-10-12', 'status' => 'active']);
        ShiftRuleViolation::factory()->create(['user_id' => $notScheduled->id, 'violation_date' => '2026-10-07', 'status' => 'active']);

        $this->assertSame(2, $this->cell()['open_violations']);
    }

    #[Test]
    public function summary_counts_each_violation_once_across_crafts(): void
    {
        $qualification = ShiftQualification::factory()->create();
        $otherCraft = Craft::factory()->create();
        $user = User::factory()->create();

        $this->makeShift()->users()->attach($user->id, ['shift_qualification_id' => $qualification->id]);
        $this->makeShift([], $otherCraft)->users()->attach($user->id, ['shift_qualification_id' => $qualification->id]);
        ShiftRuleViolation::factory()->create(['user_id' => $user->id, 'violation_date' => '2026-10-07', 'status' => 'active']);

        $from = Carbon::parse(self::MONDAY);
        $result = $this->service->compute($from, $from->copy()->addDays(6), [$this->craft->id, $otherCraft->id]);

        $this->assertSame(1, $result['rows'][$this->craft->id][self::WEEK_KEY]['open_violations']);
        $this->assertSame(1, $result['rows'][$otherCraft->id][self::WEEK_KEY]['open_violations']);
        $this->assertSame(1, $result['summary'][self::WEEK_KEY]['open_violations']);
        $this->assertSame(2, $result['summary'][self::WEEK_KEY]['crafts_with_shifts']);
        $this->assertSame(0, $result['summary'][self::WEEK_KEY]['crafts_committed']);
    }

    #[Test]
    public function staffing_counts_demand_and_regular_assignments_but_not_overbooked(): void
    {
        $qualification = ShiftQualification::factory()->create();
        $otherQualification = ShiftQualification::factory()->create();
        $shift = $this->makeShift();
        ShiftsQualifications::factory()->create([
            'shift_id' => $shift->id,
            'shift_qualification_id' => $qualification->id,
            'value' => 2,
        ]);
        ShiftsQualifications::factory()->create([
            'shift_id' => $shift->id,
            'shift_qualification_id' => $otherQualification->id,
            'value' => 0,
        ]);

        $shift->users()->attach(User::factory()->create()->id, ['shift_qualification_id' => $qualification->id]);
        $shift->users()->attach(User::factory()->create()->id, [
            'shift_qualification_id' => $qualification->id,
            'is_overbooked' => true,
        ]);
        // Zuweisung auf eine Funktion ohne Bedarf zählt nicht als besetzt
        $shift->users()->attach(User::factory()->create()->id, ['shift_qualification_id' => $otherQualification->id]);

        $cell = $this->cell();

        $this->assertSame(2, $cell['required_slots']);
        $this->assertSame(1, $cell['staffed_slots']);
        $this->assertSame(1, $cell['open_slots']);
    }

    #[Test]
    public function deadline_is_monday_minus_deadline_days_with_state_by_today(): void
    {
        $this->craft->update(['commit_request_deadline_days' => 5]);
        $this->makeShift();

        $cell = $this->cell(today: Carbon::parse('2026-09-20'));
        $this->assertSame('2026-09-30', $cell['deadline_date']);
        $this->assertSame('30.09.2026', $cell['deadline_date_formatted']);
        $this->assertSame('ok', $cell['deadline_state']);

        $this->assertSame('due_soon', $this->cell(today: Carbon::parse('2026-09-27'))['deadline_state']);
        $this->assertSame('due_soon', $this->cell(today: Carbon::parse('2026-09-30'))['deadline_state']);
        $this->assertSame('overdue', $this->cell(today: Carbon::parse('2026-10-01'))['deadline_state']);
    }

    #[Test]
    public function deadline_is_met_for_committed_and_requested_weeks(): void
    {
        $this->craft->update(['commit_request_deadline_days' => 5]);
        $overdueDay = Carbon::parse('2026-10-03');

        $this->makeShift(['is_committed' => true]);
        $this->assertSame('ok', $this->cell(today: $overdueDay)['deadline_state']);

        $this->makeShift();
        $this->assertSame('overdue', $this->cell(today: $overdueDay)['deadline_state']);

        $this->makeRequest('pending');
        $this->assertSame('ok', $this->cell(today: $overdueDay)['deadline_state']);
    }

    #[Test]
    public function shifts_outside_the_range_or_of_other_crafts_are_ignored(): void
    {
        $this->makeShift([], null, '2026-10-04');
        $this->makeShift([], null, '2026-10-12');
        $this->makeShift([], Craft::factory()->create());

        $this->assertSame('none', $this->cell()['status']);
    }

    #[Test]
    public function range_is_rounded_to_full_weeks_and_capped_at_26_weeks(): void
    {
        [$from, $to] = $this->service->normalizeRange(Carbon::parse('2026-10-07'), Carbon::parse('2027-12-31'));

        $this->assertSame('2026-10-05', $from->toDateString());
        $this->assertSame('2027-04-04', $to->toDateString());
        $this->assertCount(26, $this->service->weeks($from, $to));

        $result = $this->service->compute(Carbon::parse('2026-10-07'), Carbon::parse('2027-12-31'), [$this->craft->id]);
        $this->assertCount(26, $result['weeks']);
        $this->assertSame('2026-W41', $result['weeks'][0]['key']);
        $this->assertSame(41, $result['weeks'][0]['week_number']);
        $this->assertSame(2026, $result['weeks'][0]['year']);
        $this->assertSame('05.10.2026', $result['weeks'][0]['monday_formatted']);
        $this->assertSame('11.10.2026', $result['weeks'][0]['sunday_formatted']);
        $this->assertCount(26, $result['rows'][$this->craft->id]);
    }

    #[Test]
    public function iso_week_key_spans_year_boundary(): void
    {
        // 2026-12-28 (Mo) … 2027-01-03 (So) = ISO-KW 53/2026
        $this->makeShift([], null, '2027-01-01');

        $from = Carbon::parse('2026-12-28');
        $result = $this->service->compute($from, $from->copy()->addDays(6), [$this->craft->id]);

        $this->assertSame('2026-W53', $result['weeks'][0]['key']);
        $this->assertSame(1, $result['rows'][$this->craft->id]['2026-W53']['shifts_total']);
    }

    #[Test]
    public function without_crafts_no_queries_are_executed(): void
    {
        $from = Carbon::parse(self::MONDAY);

        $count = $this->countQueries(fn () => $this->service->compute($from, $from->copy()->addWeeks(8), []));

        $this->assertSame(0, $count);
    }

    #[Test]
    public function query_budget_is_constant_over_crafts_and_weeks(): void
    {
        $qualification = ShiftQualification::factory()->create();
        $crafts = Craft::factory()->count(3)->create(['commit_request_deadline_days' => 3]);

        foreach ($crafts as $craft) {
            for ($week = 0; $week < 8; $week++) {
                $date = Carbon::parse(self::MONDAY)->addWeeks($week)->addDays(1)->toDateString();
                $shift = $this->makeShift(['is_committed' => $week % 2 === 0], $craft, $date);
                ShiftsQualifications::factory()->create([
                    'shift_id' => $shift->id,
                    'shift_qualification_id' => $qualification->id,
                    'value' => 1,
                ]);
                $user = User::factory()->create();
                $shift->users()->attach($user->id, ['shift_qualification_id' => $qualification->id]);
                $this->makeChange($shift);
                ShiftRuleViolation::factory()->create(['user_id' => $user->id, 'violation_date' => $date, 'status' => 'active']);
                $this->makeRequest('pending', $craft, 41 + $week);
            }
        }

        $from = Carbon::parse(self::MONDAY);

        $small = $this->countQueries(
            fn () => $this->service->compute($from, $from->copy()->addDays(6), [$crafts[0]->id])
        );
        $large = $this->countQueries(
            fn () => $this->service->compute($from, $from->copy()->addWeeks(8)->subDay(), $crafts->pluck('id')->all())
        );

        $this->assertSame($small, $large);
        $this->assertLessThanOrEqual(7, $large);
    }

    private function countQueries(callable $callback): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $callback();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }
}
