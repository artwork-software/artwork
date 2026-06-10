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
        $holidays = new Collection(['holiday']);

        $dto = new CalendarPeriodDTO(
            date: '2026-05-10',
            holidays: $holidays,
            hoursOfDay: ['08:00', '09:00'],
            isExtraRow: false,
        );

        $this->assertSame('2026-05-10', $dto->date);
        $this->assertSame($holidays, $dto->holidays);
        $this->assertSame(['08:00', '09:00'], $dto->hoursOfDay);
        $this->assertFalse($dto->isExtraRow);
    }

    #[Test]
    public function holidays_and_hours_can_be_null(): void
    {
        $dto = new CalendarPeriodDTO(
            date: '2026-05-10',
            holidays: null,
            hoursOfDay: null,
            isExtraRow: true,
        );

        $this->assertNull($dto->holidays);
        $this->assertNull($dto->hoursOfDay);
        $this->assertTrue($dto->isExtraRow);
    }

    #[Test]
    public function to_array_contains_period_keys(): void
    {
        $dto = new CalendarPeriodDTO(
            date: '2026-05-10',
            holidays: null,
            hoursOfDay: null,
            isExtraRow: false,
        );

        $array = $dto->toArray();
        $this->assertArrayHasKey('date', $array);
        $this->assertArrayHasKey('holidays', $array);
        $this->assertArrayHasKey('hoursOfDay', $array);
        $this->assertArrayHasKey('isExtraRow', $array);
        $this->assertSame('2026-05-10', $array['date']);
    }
}
