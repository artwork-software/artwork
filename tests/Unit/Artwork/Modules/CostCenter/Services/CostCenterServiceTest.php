<?php

namespace Tests\Unit\Artwork\Modules\CostCenter\Services;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Artwork\Modules\CostCenter\Services\CostCenterService;
use Artwork\Modules\CostCenter\Repositories\CostCenterRepository;
use Artwork\Modules\CostCenter\Models\CostCenter;

class CostCenterServiceTest extends TestCase
{
    use WithFaker, WithoutMiddleware;

    private CostCenterRepository $costCenterRepository;
    private CostCenterService $costCenterService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->costCenterRepository = $this->createMock(CostCenterRepository::class);
        $this->costCenterService = new CostCenterService($this->costCenterRepository);
    }

    public function testFindOrCreateCostCenterCreatesNewCostCenterWhenNotFound(): void
    {
        $name = 'New Cost Center';
        $this->costCenterRepository->method('findCostCenterByName')->with($name)->willReturn(null);
        $this->costCenterRepository->method('save')->willReturnCallback(function (CostCenter $costCenter) {
            $costCenter->id = 1;
            return $costCenter;
        });

        $result = $this->costCenterService->findOrCreateCostCenter($name);

        $this->assertInstanceOf(CostCenter::class, $result);
        $this->assertEquals($name, $result->name);
        $this->assertEquals(1, $result->id);
    }

    public function testFindOrCreateCostCenterReturnsExistingCostCenterWhenFound(): void
    {
        $name = 'Existing Cost Center';
        $existingCostCenter = new CostCenter();
        $existingCostCenter->id = 1;
        $existingCostCenter->name = $name;

        $this->costCenterRepository->method('findCostCenterByName')->with($name)->willReturn($existingCostCenter);

        $result = $this->costCenterService->findOrCreateCostCenter($name);

        $this->assertInstanceOf(CostCenter::class, $result);
        $this->assertEquals($name, $result->name);
        $this->assertEquals(1, $result->id);
    }
}
