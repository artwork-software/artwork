<?php

namespace Tests\Unit\Pure\DTOs\Calendar;

use Artwork\Modules\Calendar\DTO\RoomDTO;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class RoomDTOTest extends TestCase
{
    #[Test]
    public function it_creates_from_array(): void
    {
        $dto = RoomDTO::from([
            'id' => 42,
            'name' => 'Studio A',
            'has_events' => true,
            'admins' => [['id' => 1]],
            'everyone_can_book' => false,
            'requestable_by' => [['id' => 2]],
        ]);

        $this->assertSame(42, $dto->id);
        $this->assertSame('Studio A', $dto->name);
        $this->assertTrue($dto->has_events);
        $this->assertFalse($dto->everyone_can_book);
    }

    #[Test]
    public function default_flags_are_false(): void
    {
        $dto = new RoomDTO(1, 'Room');
        $this->assertFalse($dto->has_events);
        $this->assertFalse($dto->everyone_can_book);
    }

    #[Test]
    public function to_array_contains_required_keys(): void
    {
        $dto = new RoomDTO(1, 'Room', true);
        $array = $dto->toArray();

        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('has_events', $array);
        $this->assertArrayHasKey('everyone_can_book', $array);
    }

    #[Test]
    public function constructor_accepts_array_admins(): void
    {
        $dto = new RoomDTO(1, 'A', false, [['id' => 99]]);
        $this->assertSame([['id' => 99]], $dto->admins);
    }
}
