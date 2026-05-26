<?php

namespace Tests\Unit\Pure\Resources\Department;

use Artwork\Modules\Department\Http\Resources\DepartmentIconResource;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class DepartmentIconResourceTest extends TestCase
{
    #[Test]
    public function it_transforms_department_into_array(): void
    {
        $model = (object) ['id' => 1, 'name' => 'Sound', 'svg_name' => 'icon_sound'];

        $array = (new DepartmentIconResource($model))->toArray(new Request());

        $this->assertSame(1, $array['id']);
        $this->assertSame('Sound', $array['name']);
        $this->assertSame('icon_sound', $array['svg_name']);
        $this->assertSame('DepartmentIconResource', $array['resource']);
    }

    #[Test]
    public function it_defaults_svg_name_to_icon_ausstellung_when_null(): void
    {
        $array = (new DepartmentIconResource((object) [
            'id' => 1, 'name' => 'X', 'svg_name' => null,
        ]))->toArray(new Request());

        $this->assertSame('icon_ausstellung', $array['svg_name']);
    }

    #[Test]
    public function wrap_is_null(): void
    {
        $this->assertNull(DepartmentIconResource::$wrap);
    }
}
