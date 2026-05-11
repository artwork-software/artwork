<?php

namespace Tests\Unit\Pure\DTOs\Calendar;

use Artwork\Modules\Calendar\DTO\CalendarFrontendDataDTO;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class CalendarFrontendDataDTOTest extends TestCase
{
    #[Test]
    public function it_creates_from_array(): void
    {
        $dto = CalendarFrontendDataDTO::from(['rooms' => [['id' => 1], ['id' => 2]]]);

        $this->assertSame([['id' => 1], ['id' => 2]], $dto->rooms);
    }

    #[Test]
    public function it_accepts_empty_rooms_array(): void
    {
        $dto = CalendarFrontendDataDTO::from(['rooms' => []]);
        $this->assertSame([], $dto->rooms);
    }

    #[Test]
    public function to_array_returns_expected_structure(): void
    {
        $dto = new CalendarFrontendDataDTO(['some' => 'rooms']);
        $array = $dto->toArray();

        $this->assertArrayHasKey('rooms', $array);
        $this->assertSame(['some' => 'rooms'], $array['rooms']);
    }
}
