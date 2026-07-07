<?php

namespace Tests\Unit\Pure\DTOs\Calendar;

use Artwork\Modules\Calendar\DTO\MinimalEventDTO;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class MinimalEventDTOTest extends TestCase
{
    #[Test]
    public function it_creates_from_array(): void
    {
        $dto = MinimalEventDTO::from([
            'id' => 5,
            'start' => '2026-05-10 09:00',
            'end' => '2026-05-10 11:00',
            'roomId' => 3,
        ]);

        $this->assertSame(5, $dto->id);
        $this->assertSame('2026-05-10 09:00', $dto->start);
        $this->assertSame('2026-05-10 11:00', $dto->end);
        $this->assertSame(3, $dto->roomId);
    }

    #[Test]
    public function is_minimal_defaults_to_true(): void
    {
        $dto = new MinimalEventDTO(1, '2026-01-01 00:00', '2026-01-01 23:59', 1);
        $this->assertTrue($dto->isMinimal);
    }

    #[Test]
    public function to_array_includes_is_minimal_flag(): void
    {
        $dto = new MinimalEventDTO(1, '2026-01-01 00:00', '2026-01-01 23:59', 1);
        $array = $dto->toArray();
        $this->assertArrayHasKey('isMinimal', $array);
        $this->assertTrue($array['isMinimal']);
    }
}
