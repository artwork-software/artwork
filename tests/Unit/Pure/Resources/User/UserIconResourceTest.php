<?php

namespace Tests\Unit\Pure\Resources\User;

use Artwork\Modules\User\Http\Resources\UserIconResource;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class UserIconResourceTest extends TestCase
{
    #[Test]
    public function it_transforms_user_to_array(): void
    {
        $model = (object) ['id' => 21, 'profile_photo_url' => 'https://x.test/y.png'];

        $array = (new UserIconResource($model))->toArray(new Request());

        $this->assertSame('UserIconResource', $array['resource']);
        $this->assertSame(21, $array['id']);
        $this->assertSame('https://x.test/y.png', $array['profile_photo_url']);
    }

    #[Test]
    public function wrap_is_null(): void
    {
        $this->assertNull(UserIconResource::$wrap);
    }

    #[Test]
    public function array_has_resource_key(): void
    {
        $array = (new UserIconResource((object) ['id' => 1, 'profile_photo_url' => null]))->toArray(new Request());
        $this->assertArrayHasKey('resource', $array);
    }
}
