<?php

namespace Tests\Unit\Pure\Resources\Room;

use Artwork\Modules\Room\Http\Resources\RoomPdfResource;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class RoomPdfResourceTest extends TestCase
{
    #[Test]
    public function it_transforms_model_into_expected_array(): void
    {
        $array = (new RoomPdfResource((object) ['id' => 12, 'name' => 'Main']))->toArray(new Request());

        $this->assertSame(['id' => 12, 'name' => 'Main'], $array);
    }

    #[Test]
    public function wrap_is_null(): void
    {
        $this->assertNull(RoomPdfResource::$wrap);
    }

    #[Test]
    public function array_has_two_keys(): void
    {
        $array = (new RoomPdfResource((object) ['id' => 1, 'name' => '']))->toArray(new Request());
        $this->assertCount(2, $array);
    }
}
