<?php

namespace Tests\Unit\Artwork\Modules\Craft\Repositories;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Craft\Repositories\CraftRepository;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

class CraftRepositoryTest extends TestCase
{
    protected CraftRepository $craftRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->craftRepository = new CraftRepository();
    }

    public function testSyncUsers(): void
    {
        $craft = Craft::factory()->create();
        $userIds = User::factory(3)->create()->pluck('id')->toArray();

        $result = $this->craftRepository->syncUsers($craft, $userIds);

        $this->assertIsArray($result);
        $this->assertEquals($userIds, $result['attached']);
    }

    public function testDetachUsers(): void
    {
        $craft = Craft::factory()->hasUsers(3)->create();

        $result = $this->craftRepository->detachUsers($craft);

        $this->assertEquals(3, $result);
    }

    public function testGetAll(): void
    {
        $currentCount = Craft::all()->count();
        $createCount = 3;

        Craft::factory()->count($createCount)->create();

        $result = $this->craftRepository->getAll();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(($currentCount + $createCount), $result);
    }
}
