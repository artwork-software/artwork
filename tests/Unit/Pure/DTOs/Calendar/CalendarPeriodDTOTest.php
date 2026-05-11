<?php

namespace Tests\Unit\Pure\DTOs\Calendar;

use Artwork\Modules\Calendar\DTO\CalendarPeriodDTO;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class CalendarPeriodDTOTest extends TestCase
{
    #[Test]
    public function it_creates_via_constructor(): void
    {
        $dto = new CalendarPeriodDTO(
            day: '2026-05-10',
            dayString: 'Sun',
            isWeekend: true,
            fullDay: 'Sunday',
            shortDay: 'Sun',
            withoutFormat: '20260510',
            fullDayDisplay: '10.05.',
            weekNumber: 19,
            isMonday: false,
            monthNumber: 5,
            isSunday: true,
            isFirstDayOfMonth: false,
            addWeekSeparator: false,
            holidays: new Collection(),
            hoursOfDay: ['08:00', '09:00'],
            isExtraRow: false,
        );

        $this->assertSame('2026-05-10', $dto->day);
        $this->assertTrue($dto->isWeekend);
        $this->assertSame(19, $dto->weekNumber);
        $this->assertSame(5, $dto->monthNumber);
        $this->assertTrue($dto->isSunday);
        $this->assertFalse($dto->isMonday);
        $this->assertFalse($dto->isFirstDayOfMonth);
        $this->assertFalse($dto->isExtraRow);
        $this->assertSame(['08:00', '09:00'], $dto->hoursOfDay);
    }

    #[Test]
    public function holidays_can_be_null(): void
    {
        $dto = new CalendarPeriodDTO(
            day: '2026-05-10',
            dayString: 'Sun',
            isWeekend: true,
            fullDay: 'Sunday',
            shortDay: 'Sun',
            withoutFormat: '20260510',
            fullDayDisplay: '10.05.',
            weekNumber: 19,
            isMonday: false,
            monthNumber: 5,
            isSunday: true,
            isFirstDayOfMonth: false,
            addWeekSeparator: false,
            holidays: null,
            hoursOfDay: null,
            isExtraRow: false,
        );

        $this->assertNull($dto->holidays);
        $this->assertNull($dto->hoursOfDay);
    }

    #[Test]
    public function to_array_contains_period_keys(): void
    {
        $dto = new CalendarPeriodDTO(
            day: '2026-05-10',
            dayString: 'Sun',
            isWeekend: true,
            fullDay: 'Sunday',
            shortDay: 'Sun',
            withoutFormat: '20260510',
            fullDayDisplay: '10.05.',
            weekNumber: 19,
            isMonday: false,
            monthNumber: 5,
            isSunday: true,
            isFirstDayOfMonth: false,
            addWeekSeparator: false,
            holidays: null,
            hoursOfDay: null,
            isExtraRow: false,
        );

        $array = $dto->toArray();
        $this->assertArrayHasKey('day', $array);
        $this->assertArrayHasKey('weekNumber', $array);
        $this->assertArrayHasKey('isWeekend', $array);
        $this->assertArrayHasKey('isSunday', $array);
        $this->assertArrayHasKey('isExtraRow', $array);
    }
}
