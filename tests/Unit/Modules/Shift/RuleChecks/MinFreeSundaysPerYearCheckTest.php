<?php

namespace Tests\Unit\Modules\Shift\RuleChecks;

use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\Shift\RuleChecks\MinFreeSundaysPerYearCheck;
use Artwork\Modules\Shift\RuleChecks\ShiftRuleCheckContext;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\CreatesShiftRuleFixtures;
use Tests\TestCase;

/**
 * Mindestens X freie Sonntage pro Kalenderjahr (ArbZG § 11, Standard 15): Verstoß ohne Schicht auf den
 * letzten Sonntag des Jahres, sobald freie Sonntage (vergangen + noch möglich) < X.
 *
 * Die Tests arbeiten im NÄCHSTEN Kalenderjahr, damit "heute" keine bereits vergangenen freien Sonntage
 * beisteuert.
 */
final class MinFreeSundaysPerYearCheckTest extends TestCase
{
    use CreatesShiftRuleFixtures;

    private MinFreeSundaysPerYearCheck $check;

    protected function setUp(): void
    {
        parent::setUp();
        $this->check = new MinFreeSundaysPerYearCheck();
    }

    private function rule(float $target = 0.0): ShiftRule
    {
        return ShiftRule::factory()->create([
            'trigger_type' => 'minFreeSundaysPerYear',
            'individual_number_value' => $target,
            'is_active' => true,
        ]);
    }

    private function nextYear(): int
    {
        return (int) Carbon::today()->year + 1;
    }

    private function sundaysBetween(Carbon $from, Carbon $to): int
    {
        $count = 0;
        for ($day = $from->copy(); $day->lte($to); $day->addDay()) {
            if ($day->isSunday()) {
                $count++;
            }
        }

        return $count;
    }

    #[Test]
    public function violation_when_the_remaining_free_sundays_cannot_reach_the_default_of_15(): void
    {
        [$user] = $this->userWithContract();
        $year = $this->nextYear();
        $yearStart = Carbon::create($year, 1, 1);
        $yearEnd = Carbon::create($year, 12, 31);
        // Ganztägige individuelle Zeit vom 1.1. bis 10.12. belegt alle Sonntage bis dahin
        $blockedUntil = Carbon::create($year, 12, 10);
        $this->individualTimeFor($user, $yearStart, null, null, 0, $blockedUntil);
        $expectedPossible = $this->sundaysBetween($blockedUntil->copy()->addDay(), $yearEnd);

        $violations = $this->check->check($this->rule(0.0), $user, Carbon::create($year, 3, 1), Carbon::create($year, 3, 14));

        $this->assertCount(1, $violations);
        $violation = $violations->first();
        $this->assertNull($violation->shift_id);
        $this->assertSame('warning', $violation->severity);
        $this->assertSame($year, $violation->violation_data['year']);
        $this->assertSame(15, $violation->violation_data['target']);
        $this->assertSame(0, $violation->violation_data['have']);
        $this->assertSame($expectedPossible, $violation->violation_data['possible']);
        $this->assertLessThan(15, $expectedPossible);
        $this->assertSame(Carbon::SUNDAY, $violation->violation_date->dayOfWeek);
        $this->assertSame($year, (int) $violation->violation_date->year);
        $this->assertGreaterThanOrEqual(25, (int) $violation->violation_date->day);
        $this->assertSame(12, (int) $violation->violation_date->month);
    }

    #[Test]
    public function no_violation_while_enough_sundays_are_free(): void
    {
        [$user] = $this->userWithContract();
        $year = $this->nextYear();
        // Nur Wochentage belegt: Mo–Fr in den ersten 20 Wochen — alle Sonntage frei
        $monday = Carbon::create($year, 1, 1)->next(Carbon::MONDAY);
        for ($week = 0; $week < 20; $week++) {
            $this->individualTimeFor($user, $monday->copy()->addWeeks($week), '09:00', '17:00', 0, $monday->copy()->addWeeks($week)->addDays(4));
        }

        $violations = $this->check->check($this->rule(15.0), $user, Carbon::create($year, 2, 1), Carbon::create($year, 2, 14));

        $this->assertCount(0, $violations);
    }

    #[Test]
    public function rule_value_overrides_the_default_target(): void
    {
        [$user] = $this->userWithContract();
        $year = $this->nextYear();
        $yearEnd = Carbon::create($year, 12, 31);
        $blockedUntil = Carbon::create($year, 12, 10);
        $this->individualTimeFor($user, Carbon::create($year, 1, 1), null, null, 0, $blockedUntil);
        $free = $this->sundaysBetween($blockedUntil->copy()->addDay(), $yearEnd);

        // Ziel = genau die freien Sonntage -> erreichbar, kein Verstoß
        $violations = $this->check->check($this->rule((float) $free), $user, Carbon::create($year, 6, 1), Carbon::create($year, 6, 14));
        $this->assertCount(0, $violations);

        // Ziel eins höher -> nicht mehr erreichbar
        $violations = $this->check->check($this->rule((float) ($free + 1)), $user, Carbon::create($year, 6, 1), Carbon::create($year, 6, 14));
        $this->assertCount(1, $violations);
        $this->assertSame($free + 1, $violations->first()->violation_data['target']);
    }

    #[Test]
    public function a_saturday_shift_past_midnight_occupies_the_sunday(): void
    {
        [$user] = $this->userWithContract();
        $year = $this->nextYear();
        $yearStart = Carbon::create($year, 1, 1);
        $yearEnd = Carbon::create($year, 12, 31);
        $allSundays = $this->sundaysBetween($yearStart, $yearEnd);

        $saturday = Carbon::create($year, 3, 1)->next(Carbon::SATURDAY);
        $this->shiftFor($user, $saturday, '22:00:00', '02:00:00', [], $saturday->copy()->addDay());

        // Ziel = alle Sonntage des Jahres: durch die Nachtschicht ist einer belegt -> Verstoß
        $violations = $this->check->check($this->rule((float) $allSundays), $user, Carbon::create($year, 3, 1), Carbon::create($year, 3, 14));

        $this->assertCount(1, $violations);
        $this->assertSame($allSundays - 1, $violations->first()->violation_data['possible']);
    }

    #[Test]
    public function covered_range_spans_the_whole_calendar_years(): void
    {
        $year = $this->nextYear();
        $rule = $this->rule();

        [$from, $to] = $this->check->getCoveredRange($rule, Carbon::create($year, 3, 1), Carbon::create($year, 3, 14));

        $this->assertSame("{$year}-01-01", $from->toDateString());
        $this->assertSame("{$year}-12-31", $to->toDateString());
    }

    /**
     * Das Jahresfenster darf den gemeinsamen Datenkontext nicht auf ein Jahr aufblähen (kein
     * Kontextbeitrag); mit einem schmalen Kontext fällt der Check für das Jahr auf Direktabfragen zurück
     * und liefert dasselbe Ergebnis wie ohne Kontext.
     */
    #[Test]
    public function context_range_is_null_and_a_narrow_context_does_not_change_the_result(): void
    {
        [$user] = $this->userWithContract();
        $year = $this->nextYear();
        $yearEnd = Carbon::create($year, 12, 31);
        $blockedUntil = Carbon::create($year, 12, 10);
        $this->individualTimeFor($user, Carbon::create($year, 1, 1), null, null, 0, $blockedUntil);
        $expectedPossible = $this->sundaysBetween($blockedUntil->copy()->addDay(), $yearEnd);
        $rule = $this->rule(0.0);
        $start = Carbon::create($year, 3, 1);
        $end = Carbon::create($year, 3, 14);

        $this->assertNull($this->check->getContextRange($rule, $start->copy(), $end->copy()));

        $this->check->setContext(ShiftRuleCheckContext::forRange($user, $start->copy(), $end->copy()));
        try {
            $violations = $this->check->check($rule, $user, $start->copy(), $end->copy());
        } finally {
            $this->check->setContext(null);
        }

        $this->assertCount(1, $violations);
        $this->assertSame(0, $violations->first()->violation_data['have']);
        $this->assertSame($expectedPossible, $violations->first()->violation_data['possible']);
    }
}
