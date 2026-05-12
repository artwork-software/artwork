<?php

namespace Tests\Unit\Pure\Resources\Project;

use Artwork\Modules\Project\Http\Resources\ProjectIndexAdminResource;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ProjectIndexAdminResourceTest extends TestCase
{
    #[Test]
    public function it_transforms_project_to_array(): void
    {
        $managers = ['mgr1', 'mgr2'];
        $writers = ['wr1'];
        $model = (object) [
            'id' => 9,
            'name' => 'Proj X',
            'access_budget' => true,
            'managerUsers' => $managers,
            'writeUsers' => $writers,
        ];

        $array = (new ProjectIndexAdminResource($model))->toArray(new Request());

        $this->assertSame('ProjectIndexAdminResource', $array['resource']);
        $this->assertSame(9, $array['id']);
        $this->assertSame('Proj X', $array['name']);
        $this->assertTrue($array['access_budget']);
        $this->assertSame($managers, $array['project_managers']);
        $this->assertSame($writers, $array['can_write']);
    }

    #[Test]
    public function wrap_is_null(): void
    {
        $this->assertNull(ProjectIndexAdminResource::$wrap);
    }
}
