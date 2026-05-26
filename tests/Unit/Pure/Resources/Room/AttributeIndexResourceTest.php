<?php

namespace Tests\Unit\Pure\Resources\Room;

use Artwork\Modules\Room\Http\Resources\AttributeIndexResource;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class AttributeIndexResourceTest extends TestCase
{
    #[Test]
    public function it_transforms_model_into_expected_array(): void
    {
        $model = (object) [
            'id' => 5,
            'name' => 'Sound',
            'created_at' => '2026-01-01 00:00:00',
            'updated_at' => '2026-02-01 12:00:00',
        ];

        $array = (new AttributeIndexResource($model))->toArray(new Request());

        $this->assertSame(5, $array['id']);
        $this->assertSame('Sound', $array['name']);
        $this->assertSame('2026-01-01 00:00:00', $array['created_at']);
        $this->assertSame('2026-02-01 12:00:00', $array['updated_at']);
    }

    #[Test]
    public function array_has_exact_keys(): void
    {
        $array = (new AttributeIndexResource((object) [
            'id' => 1, 'name' => '', 'created_at' => null, 'updated_at' => null,
        ]))->toArray(new Request());

        $this->assertSame(
            ['id', 'name', 'created_at', 'updated_at'],
            array_keys($array)
        );
    }
}
