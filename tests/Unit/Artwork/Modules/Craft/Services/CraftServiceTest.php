<?php

namespace Tests\Unit\Artwork\Modules\Craft\Services;

use App\Models\User;
use Artwork\Modules\Craft\Http\Requests\CraftStoreRequest;
use Artwork\Modules\Craft\Http\Requests\CraftUpdateRequest;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Craft\Services\CraftService;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

class CraftServiceTest extends TestCase
{
    protected CraftService $craftService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->craftService = app()->get(CraftService::class);
    }

    public function testGetAll(): void
    {
        $currentCount = $this->craftService->getAll()->count();
        $createCount = 3;

        Craft::factory($createCount)->create();

        $result = $this->craftService->getAll();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(($currentCount + $createCount), $result);
    }

    public function testStoreByRequest(): void
    {
        $craftStoreRequest = new CraftStoreRequest();
        $craftStoreRequest->merge([
            'name' => 'Test Craft',
            'abbreviation' => 'TC',
            'assignable_by_all' => true,
            'users' => User::factory(2)->create()->pluck('id')->toArray(),
        ]);

        $this->craftService->storeByRequest($craftStoreRequest);
        $this->assertDatabaseHas('crafts', ['name' => 'Test Craft', 'abbreviation' => 'TC']);
    }

    public function testUpdateByRequest(): void
    {
        $craftUpdateRequest = new CraftUpdateRequest();
        $userIds = User::factory(2)->create()->pluck('id')->toArray();
        $craftUpdateRequest->merge([
            'name' => 'Updated Craft',
            'abbreviation' => 'UC',
            'assignable_by_all' => true,
            'users' => $userIds,
        ]);

        $craft = Craft::factory()->create();
        $this->assertDatabaseHas('crafts', ['name' => $craft->name, 'abbreviation' => $craft->abbreviation]);
        $this->assertDatabaseMissing('crafts', ['name' => 'Updated Craft', 'abbreviation' => $craft->abbreviation]);
        $this->craftService->updateByRequest($craftUpdateRequest, $craft);
        $this->assertDatabaseHas('crafts', ['name' => 'Updated Craft', 'abbreviation' => $craft->abbreviation]);
    }

    public function testDelete(): void
    {
        $craft = Craft::factory()->create();
        $this->craftService->delete($craft);
        $this->assertDatabaseMissing('crafts', ['id' => $craft->id]);
    }
}
