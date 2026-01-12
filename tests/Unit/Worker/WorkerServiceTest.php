<?php

namespace Tests\Unit\Worker;

use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Worker\Services\WorkerService;
use Tests\TestCase;

class WorkerServiceTest extends TestCase
{
    public function testGetWorkersForShiftPlanWithServiceProviderDoesNotFailOnVacationsRelationship(): void
    {
        $serviceProvider = ServiceProvider::query()->create([
            'provider_name' => 'Test Dienstleister',
            'can_work_shifts' => true,
        ]);

        $workerService = app(WorkerService::class);

        $workers = $workerService->getWorkersForShiftPlan(ServiceProvider::class);

        $this->assertNotNull($workers);
        $this->assertGreaterThanOrEqual(1, $workers->count());
        $this->assertTrue(
            $workers->contains(fn (ServiceProvider $worker) => $worker->id === $serviceProvider->id)
        );
    }
}
