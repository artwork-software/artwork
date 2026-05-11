<?php

namespace Tests\Unit\Pure\DTOs\Calendar;

use Artwork\Modules\Calendar\DTO\CalendarHolidayDTO;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class CalendarHolidayDTOTest extends TestCase
{
    #[Test]
    public function it_creates_from_array(): void
    {
        $dto = CalendarHolidayDTO::from([
            'name' => 'Christmas',
            'date' => '2026-12-25',
            'end_date' => '2026-12-26',
            'color' => '#ff0000',
            'subdivisions' => ['BE', 'BW'],
        ]);

        $this->assertSame('Christmas', $dto->name);
        $this->assertSame('2026-12-25', $dto->date);
        $this->assertSame('2026-12-26', $dto->end_date);
        $this->assertSame('#ff0000', $dto->color);
        $this->assertSame(['BE', 'BW'], $dto->subdivisions);
    }

    #[Test]
    public function it_accepts_null_optional_fields(): void
    {
        $dto = CalendarHolidayDTO::from([
            'name' => 'Custom Day',
            'date' => null,
            'end_date' => null,
            'color' => null,
            'subdivisions' => null,
        ]);

        $this->assertSame('Custom Day', $dto->name);
        $this->assertNull($dto->date);
        $this->assertNull($dto->end_date);
        $this->assertNull($dto->color);
        $this->assertNull($dto->subdivisions);
    }

    #[Test]
    public function to_array_returns_expected_structure(): void
    {
        $dto = new CalendarHolidayDTO('Easter', '2026-04-05', null, '#00ff00', null);
        $array = $dto->toArray();

        $this->assertSame('Easter', $array['name']);
        $this->assertSame('2026-04-05', $array['date']);
        $this->assertSame('#00ff00', $array['color']);
        $this->assertNull($array['end_date']);
        $this->assertNull($array['subdivisions']);
    }
}
