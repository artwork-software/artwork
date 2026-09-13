<?php

namespace Tests\Unit\Modules\WorkTime\Support;

use Artwork\Modules\WorkTime\Support\NightWindow;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Gemeinsame Nachtminuten-Basis für Buchung und Regelprüfung: Minuten eines Zeitraums im Nachtfenster,
 * auch über Mitternacht und über mehrere Kalendertage.
 */
final class NightWindowTest extends TestCase
{
    private function at(string $dateTime): Carbon
    {
        return Carbon::parse($dateTime);
    }

    #[Test]
    public function a_window_across_midnight_counts_evening_and_early_morning(): void
    {
        $window = new NightWindow('22:00', '06:00');

        // 20:00–06:00: 22:00–24:00 + 00:00–06:00 = 8 h
        $this->assertSame(480, $window->minutesWithin($this->at('2026-09-10 20:00'), $this->at('2026-09-11 06:00')));
        // 22:00–02:00 = 4 h, 04:00–12:00 = 2 h, 09:00–17:00 = 0
        $this->assertSame(240, $window->minutesWithin($this->at('2026-09-10 22:00'), $this->at('2026-09-11 02:00')));
        $this->assertSame(120, $window->minutesWithin($this->at('2026-09-10 04:00'), $this->at('2026-09-10 12:00')));
        $this->assertSame(0, $window->minutesWithin($this->at('2026-09-10 09:00'), $this->at('2026-09-10 17:00')));
    }

    #[Test]
    public function a_window_without_midnight_is_a_single_segment(): void
    {
        $window = new NightWindow('20:00', '23:00');

        // 18:00–01:00: nur 20:00–23:00 zählt
        $this->assertSame(180, $window->minutesWithin($this->at('2026-09-10 18:00'), $this->at('2026-09-11 01:00')));
        $this->assertSame(0, $window->minutesWithin($this->at('2026-09-10 00:00'), $this->at('2026-09-10 19:00')));
    }

    #[Test]
    public function a_period_over_several_days_sums_every_touched_night(): void
    {
        $window = new NightWindow('22:00', '06:00');

        // 10.09. 20:00 – 12.09. 02:00: Nacht 10./11. (8 h) + Nacht 11./12. bis 02:00 (4 h)
        $this->assertSame(720, $window->minutesWithin($this->at('2026-09-10 20:00'), $this->at('2026-09-12 02:00')));
    }

    #[Test]
    public function clipping_to_a_calendar_day_yields_the_per_day_split_of_the_booking(): void
    {
        $window = new NightWindow('22:00', '06:00');
        $start = $this->at('2026-09-10 23:00');
        $end = $this->at('2026-09-11 03:00');

        // Tag 1: 23:00–24:00 = 60 min, Tag 2: 00:00–03:00 = 180 min
        $this->assertSame(60, $window->minutesWithin($start, $this->at('2026-09-11 00:00')));
        $this->assertSame(180, $window->minutesWithin($this->at('2026-09-11 00:00'), $end));
        $this->assertSame(240, $window->minutesWithin($start, $end));
    }

    #[Test]
    public function empty_or_reversed_periods_count_nothing_and_seconds_are_floored(): void
    {
        $window = new NightWindow('22:00', '06:00');

        $this->assertSame(0, $window->minutesWithin($this->at('2026-09-10 23:00'), $this->at('2026-09-10 23:00')));
        $this->assertSame(0, $window->minutesWithin($this->at('2026-09-11 03:00'), $this->at('2026-09-10 23:00')));
        $this->assertSame(59, $window->minutesWithin($this->at('2026-09-10 23:00:00'), $this->at('2026-09-10 23:59:59')));
    }

    #[Test]
    public function label_and_segments_reflect_the_configured_window(): void
    {
        $window = new NightWindow('22:00', '06:00');

        $this->assertSame('22:00–06:00', $window->label());
        $segments = $window->segmentsOfDay($this->at('2026-09-10'));
        $this->assertCount(2, $segments);
        $this->assertSame('2026-09-10 00:00', $segments[0][0]->format('Y-m-d H:i'));
        $this->assertSame('2026-09-10 06:00', $segments[0][1]->format('Y-m-d H:i'));
        $this->assertSame('2026-09-10 22:00', $segments[1][0]->format('Y-m-d H:i'));
        $this->assertSame('2026-09-11 00:00', $segments[1][1]->format('Y-m-d H:i'));
    }
}
