<?php

namespace Tests\Unit\Modules\Shift\Services;

use Artwork\Modules\Shift\Services\LegalBreakCalculator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * § 4 ArbZG: bis 6:00 h keine Pause, über 6 h 30 min, über 9 h 45 min — Vergleich strikt.
 */
final class LegalBreakCalculatorTest extends TestCase
{
    /**
     * @return array<string, array{int, int}>
     */
    public static function boundaryProvider(): array
    {
        return [
            'exactly 4h → 0' => [240, 0],
            'exactly 6:00 → 0' => [360, 0],
            '6:01 → 30' => [361, 30],
            'exactly 9:00 → 30' => [540, 30],
            '9:01 → 45' => [541, 45],
            '12h → 45' => [720, 45],
            'zero minutes → 0' => [0, 0],
        ];
    }

    #[Test]
    #[DataProvider('boundaryProvider')]
    public function minimum_break_uses_strict_arbzg_boundaries(int $workMinutes, int $expectedBreak): void
    {
        $this->assertSame($expectedBreak, LegalBreakCalculator::minimumBreakMinutes($workMinutes));
    }

    #[Test]
    public function work_minutes_between_handles_same_day_ranges(): void
    {
        $this->assertSame(240, LegalBreakCalculator::workMinutesBetween('08:00', '12:00'));
        $this->assertSame(540, LegalBreakCalculator::workMinutesBetween('08:00:00', '17:00:00'));
        $this->assertSame(361, LegalBreakCalculator::workMinutesBetween('08:00', '14:01'));
    }

    #[Test]
    public function work_minutes_between_treats_end_before_start_as_over_midnight(): void
    {
        // 22:00–06:00 = 8h; der Pausenabzug bleibt komplett am ersten Tag (Entscheidung).
        $this->assertSame(480, LegalBreakCalculator::workMinutesBetween('22:00', '06:00'));
        $this->assertSame(600, LegalBreakCalculator::workMinutesBetween('20:00', '06:00'));
        $this->assertSame(45, LegalBreakCalculator::minimumBreakMinutesBetween('20:00', '06:00'));
    }

    #[Test]
    public function work_minutes_between_returns_zero_for_missing_or_invalid_times(): void
    {
        $this->assertSame(0, LegalBreakCalculator::workMinutesBetween(null, '12:00'));
        $this->assertSame(0, LegalBreakCalculator::workMinutesBetween('08:00', ''));
        $this->assertSame(0, LegalBreakCalculator::workMinutesBetween('abc', '12:00'));
    }

    #[Test]
    public function work_minutes_between_accepts_full_datetimes(): void
    {
        $this->assertSame(
            480,
            LegalBreakCalculator::workMinutesBetween('2026-05-06 22:00:00', '2026-05-07 06:00:00')
        );
    }

    #[Test]
    public function exactly_four_hours_requires_no_break(): void
    {
        $this->assertSame(0, LegalBreakCalculator::minimumBreakMinutesBetween('09:00', '13:00'));
    }

    #[Test]
    public function resolve_break_minutes_only_fills_missing_values(): void
    {
        $this->assertSame(30, LegalBreakCalculator::resolveBreakMinutes(null, '08:00', '17:00'));
        $this->assertSame(30, LegalBreakCalculator::resolveBreakMinutes('', '08:00', '17:00'));
        $this->assertSame(0, LegalBreakCalculator::resolveBreakMinutes(0, '08:00', '17:00'));
        $this->assertSame(0, LegalBreakCalculator::resolveBreakMinutes('0', '08:00', '17:00'));
        $this->assertSame(15, LegalBreakCalculator::resolveBreakMinutes(15, '08:00', '17:00'));
        $this->assertSame(0, LegalBreakCalculator::resolveBreakMinutes(null, '08:00', '12:00'));
    }
}
