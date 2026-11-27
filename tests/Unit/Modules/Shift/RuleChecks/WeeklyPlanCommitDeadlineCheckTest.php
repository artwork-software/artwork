<?php

namespace Tests\Unit\Modules\Shift\RuleChecks;

use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\Shift\RuleChecks\WeeklyPlanCommitDeadlineCheck;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\CreatesShiftRuleFixtures;
use Tests\TestCase;

/**
 * Wochenplan-Frist (NV Bühne § 6 Abs. 1): Nach Donnerstag 14 Uhr der Vorwoche müssen die Schichten der
 * Folgewoche verbindlich sein.
 */
final class WeeklyPlanCommitDeadlineCheckTest extends TestCase
{
    use CreatesShiftRuleFixtures;

    private WeeklyPlanCommitDeadlineCheck $check;

    /** Ein Montag, an dem die Testwoche beginnt */
    private Carbon $monday;

    protected function setUp(): void
    {
        parent::setUp();
        $this->check = new WeeklyPlanCommitDeadlineCheck();
        $this->monday = Carbon::create(2027, 3, 1)->startOfDay();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function rule(?array $config = null): ShiftRule
    {
        return ShiftRule::factory()->create([
            'trigger_type' => 'weeklyPlanCommitDeadline',
            'individual_number_value' => 0,
            'config' => $config,
            'is_active' => true,
        ]);
    }

    private function check(ShiftRule $rule, User $user): \Illuminate\Support\Collection
    {
        return $this->check->check($rule, $user, Carbon::now()->startOfDay(), Carbon::now()->startOfDay());
    }

    #[Test]
    public function uncommitted_shift_of_next_week_after_thursday_two_pm_is_a_violation(): void
    {
        // Donnerstag 14:01 Uhr
        Carbon::setTestNow($this->monday->copy()->addDays(3)->setTime(14, 1));
        $user = User::factory()->create();
        $nextMonday = $this->monday->copy()->addWeek();
        $this->shiftFor($user, $nextMonday->copy()->addDays(2));
        $this->shiftFor($user, $nextMonday->copy()->addDays(4));

        $violations = $this->check($this->rule(), $user);

        $this->assertCount(1, $violations);
        $violation = $violations->first();
        $this->assertNull($violation->shift_id);
        $this->assertSame($nextMonday->toDateString(), $violation->violation_date->toDateString());
        $this->assertSame(2, $violation->violation_data['open_shifts']);
        $this->assertSame(
            $this->monday->copy()->addDays(3)->format('Y-m-d') . ' 14:00',
            $violation->violation_data['deadline']
        );
        $this->assertSame('weekly_plan_commit_deadline', $violation->violation_data['type']);
    }

    #[Test]
    public function no_violation_before_the_deadline(): void
    {
        // Donnerstag 13:59 Uhr
        Carbon::setTestNow($this->monday->copy()->addDays(3)->setTime(13, 59));
        $user = User::factory()->create();
        $this->shiftFor($user, $this->monday->copy()->addWeek()->addDays(2));

        $this->assertCount(0, $this->check($this->rule(), $user));
    }

    #[Test]
    public function committed_shifts_are_fine(): void
    {
        Carbon::setTestNow($this->monday->copy()->addDays(4)->setTime(9, 0));
        $user = User::factory()->create();
        $this->shiftFor(
            $user,
            $this->monday->copy()->addWeek()->addDays(2),
            '08:00:00',
            '16:00:00',
            ['is_committed' => true]
        );

        $this->assertCount(0, $this->check($this->rule(), $user));
    }

    #[Test]
    public function open_shifts_of_the_running_week_count_from_today_on(): void
    {
        // Mittwoch: die Frist der laufenden Woche lag am Donnerstag der Vorwoche
        Carbon::setTestNow($this->monday->copy()->addDays(2)->setTime(10, 0));
        $user = User::factory()->create();
        // Montag (vorbei) zählt nicht mehr, Freitag schon
        $this->shiftFor($user, $this->monday->copy());
        $this->shiftFor($user, $this->monday->copy()->addDays(4));

        $violations = $this->check($this->rule(), $user);

        $this->assertCount(1, $violations);
        $this->assertSame($this->monday->toDateString(), $violations->first()->violation_date->toDateString());
        $this->assertSame(1, $violations->first()->violation_data['open_shifts']);
    }

    #[Test]
    public function weekday_time_and_weeks_ahead_are_configurable(): void
    {
        $rule = $this->rule(['weekday' => 2, 'time' => '10:30', 'weeks_ahead' => 2]);
        $target = $this->monday->copy()->addWeeks(2);

        $deadline = $this->check->deadlineFor($rule, $target);

        // Dienstag zwei Wochen vor der Zielwoche, 10:30 Uhr
        $this->assertSame($this->monday->copy()->addDay()->format('Y-m-d') . ' 10:30', $deadline->format('Y-m-d H:i'));
    }

    #[Test]
    public function deadline_on_a_special_day_moves_to_the_working_day_before(): void
    {
        $thursday = $this->monday->copy()->addDays(3);
        $this->holiday($thursday, 'Feiertag am Donnerstag');

        $deadline = $this->check->deadlineFor($this->rule(), $this->monday->copy()->addWeek());

        $this->assertSame($thursday->copy()->subDay()->format('Y-m-d') . ' 14:00', $deadline->format('Y-m-d H:i'));
    }

    #[Test]
    public function covered_range_spans_the_weeks_whose_deadline_has_passed(): void
    {
        // Freitag: laufende Woche und Folgewoche sind fällig
        Carbon::setTestNow($this->monday->copy()->addDays(4)->setTime(8, 0));

        $range = $this->check->getCoveredRange($this->rule(), Carbon::now(), Carbon::now());

        $this->assertNotNull($range);
        // Beginn zwölf Wochen zurück: vergangene Wochen gehören dazu, damit stehen gebliebene
        // Verstöße abgeräumt werden
        $this->assertSame($this->monday->copy()->subWeeks(12)->toDateString(), $range[0]->toDateString());
        $this->assertSame($this->monday->copy()->addDays(13)->toDateString(), $range[1]->toDateString());
    }
}
