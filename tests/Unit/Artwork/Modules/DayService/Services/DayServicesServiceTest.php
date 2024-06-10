<?php

namespace Tests\Unit\Artwork\Modules\DayService\Services;

use Artwork\Modules\DayService\Models\DayService;
use Artwork\Modules\DayService\Services\DayServicesService;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Tests\TestCase;
use Illuminate\Database\Eloquent\Collection;

class DayServicesServiceTest extends TestCase
{

    protected DayServicesService $dayServicesService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dayServicesService = app()->get(DayServicesService::class);
    }

    public function testGetAll(): void
    {
        $currentCount = $this->dayServicesService->getAll()->count();
        $createCount = 3;

        DayService::factory($createCount)->create();

        $result = $this->dayServicesService->getAll();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(($currentCount + $createCount), $result);
    }

    public function testCreate(): void
    {
        $data = [
            'name' => 'Test DayService',
            'icon' => 'icon',
            'hex_color' => '#000000',
        ];

        $this->dayServicesService->create($data);
        $this->assertDatabaseHas(
            'day_services',
            ['name' => 'Test DayService', 'icon' => 'icon', 'hex_color' => '#000000']
        );
    }

    public function testUpdate(): void
    {
        $dayService = DayService::factory()->create();
        $data = [
            'name' => 'Updated DayService',
            'icon' => 'updated_icon',
            'hex_color' => '#ffffff',
        ];

        $this->dayServicesService->update($dayService, $data);
        $this->assertDatabaseHas(
            'day_services',
            ['name' => 'Updated DayService', 'icon' => 'updated_icon', 'hex_color' => '#ffffff']
        );
    }

    public function testAttachDayServiceable(): void
    {
        $dayService = DayService::factory()->create();
        $dayServiceable = User::factory()->create();
        $date = Carbon::now()->toDateString();

        $this->dayServicesService->attachDayServiceable($dayService, $dayServiceable, $date);
        $this->assertDatabaseHas(
            'day_serviceables',
            ['day_service_id' => $dayService->id, 'day_serviceable_id' => $dayServiceable->id, 'date' => $date]
        );
    }

    public function testFindModelInstance(): void
    {
        $user = User::factory()->create();
        $modelType = 'user';
        $modelId = $user->id;

        $result = $this->dayServicesService->findModelInstance($modelType, $modelId);
        $this->assertEquals($user->id, $result->id);
    }
}
