<?php

namespace Tests\Unit\Pure\Resources\EventType;

use Artwork\Modules\EventType\Http\Resources\EventTypPdfResource;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class EventTypePdfResourceTest extends TestCase
{
    #[Test]
    public function it_transforms_model_into_expected_array(): void
    {
        $model = (object) [
            'id' => 4,
            'name' => 'Concert',
            'abbreviation' => 'CN',
            'hex_code' => '#abcdef',
        ];

        $array = (new EventTypPdfResource($model))->toArray(new Request());

        $this->assertSame([
            'id' => 4,
            'name' => 'Concert',
            'abbreviation' => 'CN',
            'hex_code' => '#abcdef',
        ], $array);
    }

    #[Test]
    public function wrap_is_null(): void
    {
        $this->assertNull(EventTypPdfResource::$wrap);
    }

    #[Test]
    public function array_has_exactly_four_keys(): void
    {
        $array = (new EventTypPdfResource((object) [
            'id' => 1, 'name' => '', 'abbreviation' => '', 'hex_code' => '',
        ]))->toArray(new Request());

        $this->assertCount(4, $array);
    }
}
