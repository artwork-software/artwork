<?php

namespace Tests\Unit\Modules\AiAssistant;

use Artwork\Modules\AiAssistant\Services\FreeSlotFinder;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * core-api-events-rooms: freie Zeitfenster auf konstruierten Belegungen (Konzept B1 §5, §8).
 *
 * Reine Rechnung, keine Datenbank — deshalb direkt auf PHPUnits TestCase.
 */
final class FreeSlotFinderTest extends TestCase
{
    private FreeSlotFinder $finder;

    /** Ein Mittwoch. */
    private CarbonImmutable $day;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = new FreeSlotFinder();
        $this->day = CarbonImmutable::parse('2026-09-16 00:00:00');
    }

    /**
     * @param list<array{0: string, 1: string}> $occupancy
     * @return array<int, list<array{start: CarbonImmutable, end: CarbonImmutable}>>
     */
    private function room(int $roomId, array $occupancy): array
    {
        return [
            $roomId => array_map(
                fn (array $entry): array => [
                    'start' => CarbonImmutable::parse($entry[0]),
                    'end' => CarbonImmutable::parse($entry[1]),
                ],
                $occupancy
            ),
        ];
    }

    #[Test]
    public function anEmptyRoomOffersTheWholeDayWindow(): void
    {
        $slots = $this->finder->find(
            $this->room(7, []),
            $this->day,
            $this->day,
            1.0
        );

        $this->assertCount(1, $slots);
        $this->assertSame(7, $slots[0]['room_id']);
        $this->assertSame('08:00', $slots[0]['start']->format('H:i'));
        $this->assertSame('22:00', $slots[0]['end']->format('H:i'));
        $this->assertSame(14.0, $slots[0]['hours']);
    }

    #[Test]
    public function aBookingSplitsTheWindowIntoTwoSlots(): void
    {
        $slots = $this->finder->find(
            $this->room(7, [['2026-09-16 12:00', '2026-09-16 14:00']]),
            $this->day,
            $this->day,
            1.0
        );

        $this->assertCount(2, $slots);
        // Laengste zuerst: 14–22 Uhr (8 h) vor 08–12 Uhr (4 h).
        $this->assertSame('14:00', $slots[0]['start']->format('H:i'));
        $this->assertSame(8.0, $slots[0]['hours']);
        $this->assertSame('08:00', $slots[1]['start']->format('H:i'));
        $this->assertSame(4.0, $slots[1]['hours']);
    }

    #[Test]
    public function overlappingBookingsAreMerged(): void
    {
        $slots = $this->finder->find(
            $this->room(7, [
                ['2026-09-16 10:00', '2026-09-16 13:00'],
                ['2026-09-16 12:00', '2026-09-16 15:00'],
                ['2026-09-16 15:00', '2026-09-16 16:00'],
            ]),
            $this->day,
            $this->day,
            1.0
        );

        $this->assertCount(2, $slots);
        $this->assertSame('16:00', $slots[0]['start']->format('H:i'));
        $this->assertSame(6.0, $slots[0]['hours']);
        $this->assertSame(2.0, $slots[1]['hours']);
    }

    /** Termine ueber Mitternacht werden am Fensterrand beschnitten, nicht ignoriert. */
    #[Test]
    public function bookingsAcrossMidnightBlockBothDays(): void
    {
        $slots = $this->finder->find(
            $this->room(7, [['2026-09-16 20:00', '2026-09-17 10:00']]),
            $this->day,
            $this->day->addDay(),
            1.0
        );

        $this->assertCount(2, $slots);
        $lengths = array_column($slots, 'hours');
        sort($lengths);

        $this->assertSame([12.0, 12.0], $lengths);
        $this->assertSame('08:00', $slots[0]['start']->format('H:i'));
        $this->assertSame('20:00', $slots[0]['end']->format('H:i'));
    }

    #[Test]
    public function aGapOfExactlyTheMinimumLengthStillCounts(): void
    {
        $slots = $this->finder->find(
            $this->room(7, [
                ['2026-09-16 08:00', '2026-09-16 10:00'],
                ['2026-09-16 12:00', '2026-09-16 22:00'],
            ]),
            $this->day,
            $this->day,
            2.0
        );

        $this->assertCount(1, $slots);
        $this->assertSame(2.0, $slots[0]['hours']);
    }

    #[Test]
    public function theWeekdayFilterKeepsOnlyTheWantedDays(): void
    {
        $slots = $this->finder->find(
            $this->room(7, []),
            $this->day,
            $this->day->addDays(6),
            1.0,
            [6, 7]
        );

        $this->assertCount(2, $slots);

        foreach ($slots as $slot) {
            $this->assertContains($slot['start']->isoWeekday(), [6, 7]);
        }
    }

    #[Test]
    public function aCustomDayWindowLimitsTheSlots(): void
    {
        $slots = $this->finder->find(
            $this->room(7, []),
            $this->day,
            $this->day,
            1.0,
            [],
            ['from' => '18:00', 'to' => '23:00']
        );

        $this->assertSame(5.0, $slots[0]['hours']);
        $this->assertSame('18:00', $slots[0]['start']->format('H:i'));
    }

    #[Test]
    public function aFullyBookedRoomOffersNothing(): void
    {
        $slots = $this->finder->find(
            $this->room(7, [['2026-09-16 07:00', '2026-09-16 23:00']]),
            $this->day,
            $this->day,
            0.5
        );

        $this->assertSame([], $slots);
    }

    #[Test]
    public function theResultIsCapped(): void
    {
        $slots = $this->finder->find(
            $this->room(7, []),
            $this->day,
            $this->day->addDays(40),
            1.0
        );

        $this->assertCount(FreeSlotFinder::MAX_SLOTS, $slots);
    }
}
