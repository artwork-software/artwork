<?php

namespace Tests\Unit\Pure\Resources\Project;

use Artwork\Modules\Project\Http\Resources\ProjectInCalendarResource;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ProjectInCalendarResourceTest extends TestCase
{
    #[Test]
    public function it_transforms_model_into_expected_array(): void
    {
        $model = (object) ['id' => 7, 'name' => 'Project Alpha', 'description' => 'desc'];

        $array = (new ProjectInCalendarResource($model))->toArray(new Request());

        $this->assertSame([
            'id' => 7,
            'name' => 'Project Alpha',
            'description' => 'desc',
        ], $array);
    }

    #[Test]
    public function wrap_is_null(): void
    {
        $this->assertNull(ProjectInCalendarResource::$wrap);
    }

    #[Test]
    public function description_can_be_null(): void
    {
        $array = (new ProjectInCalendarResource((object) [
            'id' => 1, 'name' => 'X', 'description' => null,
        ]))->toArray(new Request());

        $this->assertNull($array['description']);
    }
}
