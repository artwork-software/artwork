<?php

namespace Tests\Unit\Pure\Resources\Category;

use Artwork\Modules\Category\Http\Resources\CategoryIndexResource;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class CategoryIndexResourceTest extends TestCase
{
    #[Test]
    public function it_transforms_model_into_expected_array(): void
    {
        $model = (object) [
            'id' => 99,
            'name' => 'Theatre',
            'created_at' => '2026-01-01 00:00:00',
            'updated_at' => '2026-02-01 12:00:00',
        ];

        $resource = (new CategoryIndexResource($model))->toArray(new Request());

        $this->assertSame([
            'id' => 99,
            'name' => 'Theatre',
            'created_at' => '2026-01-01 00:00:00',
            'updated_at' => '2026-02-01 12:00:00',
        ], $resource);
    }

    #[Test]
    public function array_includes_required_keys(): void
    {
        $model = (object) [
            'id' => 1,
            'name' => 'X',
            'created_at' => null,
            'updated_at' => null,
        ];

        $array = (new CategoryIndexResource($model))->toArray(new Request());

        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('created_at', $array);
        $this->assertArrayHasKey('updated_at', $array);
    }
}
