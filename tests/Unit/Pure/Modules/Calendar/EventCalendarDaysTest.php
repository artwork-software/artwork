<?php

namespace Tests\Unit\Pure\Modules\Calendar;

use Artwork\Modules\Calendar\Services\EventCalendarDays;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class EventCalendarDaysTest extends TestCase
{
    /**
     * @return array<string, array{string, string, array<int, string>}>
     */
    public static function eventRanges(): array
    {
        return [
            'end time after start time' => [
                '2026-10-24 10:00', '2026-10-26 14:00', ['24.10.2026', '25.10.2026', '26.10.2026'],
            ],
            'end time before start time keeps the last day' => [
                '2026-10-24 15:00', '2026-10-26 14:00', ['24.10.2026', '25.10.2026', '26.10.2026'],
            ],
            'overnight event' => [
                '2026-08-30 22:30', '2026-08-31 02:00', ['30.08.2026', '31.08.2026'],
            ],
            'ending exactly at midnight stays on the start day' => [
                '2026-08-30 22:00', '2026-08-31 00:00', ['30.08.2026'],
            ],
            'multi-day ending at midnight' => [
                '2026-10-24 10:00', '2026-10-26 00:00', ['24.10.2026', '25.10.2026'],
            ],
            'single day' => [
                '2026-10-24 10:00', '2026-10-24 12:00', ['24.10.2026'],
            ],
            'broken legacy data with end before start' => [
                '2026-08-30 22:00', '2026-08-30 00:00', ['30.08.2026'],
            ],
            'zero length at midnight' => [
                '2026-08-30 00:00', '2026-08-30 00:00', ['30.08.2026'],
            ],
        ];
    }

    /**
     * @param array<int, string> $expectedDays
     */
    #[Test]
    #[DataProvider('eventRanges')]
    public function it_returns_every_calendar_day_the_event_touches(
        string $start,
        string $end,
        array $expectedDays
    ): void {
        $days = collect(EventCalendarDays::between($start, $end))
            ->map(fn($day) => $day->format('d.m.Y'))
            ->all();

        $this->assertSame($expectedDays, $days);
    }
}
