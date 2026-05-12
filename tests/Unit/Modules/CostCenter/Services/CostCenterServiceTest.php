<?php

namespace Tests\Unit\Modules\CostCenter\Services;

use Artwork\Modules\CostCenter\Models\CostCenter;
use Artwork\Modules\CostCenter\Services\CostCenterService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class CostCenterServiceTest extends TestCase
{
    private CostCenterService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CostCenterService::class);
    }

    #[Test]
    public function find_or_create_cost_center_creates_new_when_missing(): void
    {
        $this->assertDatabaseMissing('cost_centers', ['name' => 'Marketing']);

        $costCenter = $this->service->findOrCreateCostCenter('Marketing');

        $this->assertInstanceOf(CostCenter::class, $costCenter);
        $this->assertTrue($costCenter->exists);
        $this->assertSame('Marketing', $costCenter->name);
        $this->assertDatabaseHas('cost_centers', ['name' => 'Marketing']);
    }

    #[Test]
    public function find_or_create_cost_center_returns_existing_when_present(): void
    {
        $existing = CostCenter::factory()->create(['name' => 'Engineering']);

        $found = $this->service->findOrCreateCostCenter('Engineering');

        $this->assertSame($existing->id, $found->id);
        $this->assertSame(1, CostCenter::where('name', 'Engineering')->count());
    }
}
