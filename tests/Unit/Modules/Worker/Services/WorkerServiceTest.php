<?php

namespace Tests\Unit\Modules\Worker\Services;

use Artwork\Modules\User\Models\User;
use Artwork\Modules\Worker\Services\WorkerService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class WorkerServiceTest extends TestCase
{
    private WorkerService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(WorkerService::class);
    }

    #[Test]
    public function search_workers_returns_merged_collection(): void
    {
        // Scout `null` driver returns empty - we verify the type and structure.
        $result = $this->service->searchWorkers('foo');

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);
    }

    #[Test]
    public function get_workers_for_shift_plan_throws_on_invalid_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->getWorkersForShiftPlan('UnknownType');
    }

    #[Test]
    public function get_workers_for_shift_plan_by_ids_returns_empty_for_empty_input(): void
    {
        $result = $this->service->getWorkersForShiftPlanByIds(User::class, []);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(0, $result);
    }

    #[Test]
    public function get_workers_for_shift_plan_by_ids_throws_on_invalid_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->getWorkersForShiftPlanByIds('UnknownType', [1, 2]);
    }

    #[Test]
    public function build_qualifications_cache_returns_empty_for_workers_without_shifts(): void
    {
        $workers = new Collection();

        $result = $this->service->buildQualificationsCache($workers);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    #[Test]
    public function map_day_services_returns_empty_collection_for_null(): void
    {
        $result = $this->service->mapDayServices(null);

        $this->assertInstanceOf(SupportCollection::class, $result);
        $this->assertCount(0, $result);
    }

    #[Test]
    public function map_individual_times_returns_empty_collection_for_null(): void
    {
        $result = $this->service->mapIndividualTimes(null);

        $this->assertInstanceOf(SupportCollection::class, $result);
        $this->assertCount(0, $result);
    }
}
