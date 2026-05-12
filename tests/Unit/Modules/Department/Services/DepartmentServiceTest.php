<?php

namespace Tests\Unit\Modules\Department\Services;

use Artwork\Modules\Department\Events\DepartmentUpdated;
use Artwork\Modules\Department\Http\Resources\DepartmentShowResource;
use Artwork\Modules\Department\Models\Department;
use Artwork\Modules\Department\Services\DepartmentService;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class DepartmentServiceTest extends TestCase
{
    private DepartmentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(DepartmentService::class);
    }

    #[Test]
    public function search_departments_returns_mapped_collection(): void
    {
        // Scout `null` driver returns empty result -> verify type only
        $result = $this->service->searchDepartments('anything');

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);
    }

    #[Test]
    public function remove_all_members_detaches_users(): void
    {
        $department = Department::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $department->users()->attach([$user1->id, $user2->id]);

        $this->service->removeAllMembers($department);

        $this->assertSame(0, $department->users()->count());
    }

    #[Test]
    public function get_department_index_resource_returns_array(): void
    {
        Department::factory()->count(2)->create();

        $result = $this->service->getDepartmentIndexResource();

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(2, count($result));
    }

    #[Test]
    public function create_department_show_resource_returns_resource_instance(): void
    {
        $department = Department::factory()->create();

        $resource = $this->service->createDepartmentShowResource($department);

        $this->assertInstanceOf(DepartmentShowResource::class, $resource);
    }

    #[Test]
    public function broadcast_department_updated_dispatches_event(): void
    {
        Event::fake([DepartmentUpdated::class]);

        $this->service->broadcastDepartmentUpdated();

        Event::assertDispatched(DepartmentUpdated::class);
    }
}
