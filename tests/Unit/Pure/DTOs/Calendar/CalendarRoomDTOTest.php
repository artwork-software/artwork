<?php

namespace Tests\Unit\Pure\DTOs\Calendar;

use Artwork\Modules\Calendar\DTO\CalendarRoomDTO;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class CalendarRoomDTOTest extends TestCase
{
    #[Test]
    public function it_creates_from_array(): void
    {
        $dto = CalendarRoomDTO::from([
            'roomId' => 12,
            'roomName' => 'Main Hall',
            'content' => ['foo' => 'bar'],
            'eventsById' => ['1' => ['id' => 1]],
            'shiftsById' => ['10' => ['id' => 10]],
        ]);

        $this->assertSame(12, $dto->roomId);
        $this->assertSame('Main Hall', $dto->roomName);
        $this->assertSame(['foo' => 'bar'], $dto->content);
        $this->assertSame(['1' => ['id' => 1]], $dto->eventsById);
        $this->assertSame(['10' => ['id' => 10]], $dto->shiftsById);
    }

    #[Test]
    public function nullable_collections_default_to_null(): void
    {
        $dto = new CalendarRoomDTO(1, 'Test', null);
        $this->assertNull($dto->content);
        $this->assertNull($dto->eventsById);
        $this->assertNull($dto->shiftsById);
    }

    #[Test]
    public function to_array_contains_expected_keys(): void
    {
        $dto = new CalendarRoomDTO(1, 'Room', []);
        $array = $dto->toArray();

        $this->assertArrayHasKey('roomId', $array);
        $this->assertArrayHasKey('roomName', $array);
        $this->assertArrayHasKey('content', $array);
        $this->assertArrayHasKey('eventsById', $array);
        $this->assertArrayHasKey('shiftsById', $array);
    }
}
