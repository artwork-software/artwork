<?php

namespace Tests\Unit\Pure\Resources\Notification;

use Artwork\Modules\Notification\Http\Resources\NotificationProjectResource;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class NotificationProjectResourceTest extends TestCase
{
    #[Test]
    public function it_transforms_project_into_array(): void
    {
        $model = (object) [
            'id' => 1,
            'name' => 'Project Y',
            'description' => 'desc',
            'shift_description' => 'sd',
            'number_of_participants' => 5,
            'is_group' => false,
            'groups' => [],
            'sectors' => ['s1'],
            'categories' => ['c1'],
            'genres' => ['g1'],
            'key_visual_path' => null,
            'costCenter' => null,
        ];

        $array = (new NotificationProjectResource($model))->toArray(new Request());

        $this->assertSame(1, $array['id']);
        $this->assertSame('Project Y', $array['name']);
        $this->assertSame('desc', $array['description']);
        $this->assertSame('sd', $array['shiftDescription']);
        $this->assertSame(5, $array['number_of_participants']);
        $this->assertFalse($array['is_group']);
        $this->assertSame(['s1'], $array['sectors']);
        $this->assertNull($array['cost_center']);
    }

    #[Test]
    public function array_has_expected_keys(): void
    {
        $array = (new NotificationProjectResource((object) [
            'id' => 1, 'name' => '', 'description' => null, 'shift_description' => null,
            'number_of_participants' => 0, 'is_group' => false, 'groups' => null,
            'sectors' => null, 'categories' => null, 'genres' => null,
            'key_visual_path' => null, 'costCenter' => null,
        ]))->toArray(new Request());

        foreach (['id', 'name', 'description', 'shiftDescription', 'number_of_participants',
                 'is_group', 'group', 'sectors', 'categories', 'genres', 'key_visual_path',
                 'cost_center'] as $key) {
            $this->assertArrayHasKey($key, $array);
        }
    }

    #[Test]
    public function wrap_is_null(): void
    {
        $this->assertNull(NotificationProjectResource::$wrap);
    }
}
