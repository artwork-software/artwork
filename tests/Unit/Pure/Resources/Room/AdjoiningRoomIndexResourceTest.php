<?php

namespace Tests\Unit\Pure\Resources\Room;

use Artwork\Modules\Room\Http\Resources\AdjoiningRoomIndexResource;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class AdjoiningRoomIndexResourceTest extends TestCase
{
    #[Test]
    public function it_transforms_model_into_expected_array(): void
    {
        $model = (object) [
            'id' => 11,
            'name' => 'Main Hall',
            'description' => 'desc',
            'end_date' => '2026-05-10',
            'everyone_can_book' => true,
            'order' => 3,
            'start_date' => '2026-05-01',
            'temporary' => false,
            'user_id' => 7,
            'area_id' => 4,
            'created_at' => '2026-01-01 12:00:00',
            'updated_at' => '2026-02-01 12:00:00',
            'deleted_at' => null,
        ];

        $array = (new AdjoiningRoomIndexResource($model))->toArray(new Request());

        $this->assertSame(11, $array['id']);
        $this->assertSame('Main Hall', $array['name']);
        $this->assertSame('desc', $array['description']);
        $this->assertTrue($array['everyone_can_book']);
        $this->assertSame(3, $array['order']);
        $this->assertFalse($array['temporary']);
        $this->assertSame(7, $array['user_id']);
        $this->assertSame(4, $array['area_id']);
        $this->assertNull($array['deleted_at']);
    }

    #[Test]
    public function array_has_all_expected_keys(): void
    {
        $model = (object) [
            'id' => 1, 'name' => 'X', 'description' => null, 'end_date' => null,
            'everyone_can_book' => false, 'order' => 0, 'start_date' => null,
            'temporary' => false, 'user_id' => null, 'area_id' => null,
            'created_at' => null, 'updated_at' => null, 'deleted_at' => null,
        ];

        $array = (new AdjoiningRoomIndexResource($model))->toArray(new Request());

        foreach (['id', 'name', 'description', 'end_date', 'everyone_can_book', 'order',
                 'start_date', 'temporary', 'user_id', 'area_id', 'created_at',
                 'updated_at', 'deleted_at'] as $key) {
            $this->assertArrayHasKey($key, $array, sprintf('Missing key: %s', $key));
        }
    }
}
