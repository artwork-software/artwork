<?php

namespace Tests\Unit\Modules\Scheduling\Services;

use Artwork\Modules\Scheduling\Models\Scheduling;
use Artwork\Modules\Scheduling\Services\SchedulingService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class SchedulingServiceTest extends TestCase
{
    private SchedulingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SchedulingService::class);
    }

    #[Test]
    public function create_persists_new_scheduling_when_none_exists(): void
    {
        $result = $this->service->create(1, 'TASK_CHANGES', 'Task', 42);

        $this->assertTrue($result);
        $this->assertDatabaseHas('schedulings', [
            'user_id' => 1,
            'type' => 'TASK_CHANGES',
            'model' => 'Task',
            'model_id' => 42,
            'count' => 1,
        ]);
    }

    #[Test]
    public function create_increments_count_when_scheduling_exists(): void
    {
        Scheduling::create([
            'user_id' => 7,
            'type' => 'PROJECT_CHANGES',
            'model' => 'Project',
            'model_id' => 99,
            'count' => 3,
        ]);

        $result = $this->service->create(7, 'PROJECT_CHANGES', 'Project', 99);

        $this->assertTrue($result);
        $this->assertDatabaseHas('schedulings', [
            'user_id' => 7,
            'type' => 'PROJECT_CHANGES',
            'model_id' => 99,
            'count' => 4,
        ]);
    }

    #[Test]
    public function create_separates_by_user_or_type_or_model_id(): void
    {
        $this->service->create(1, 'TASK_CHANGES', 'Task', 1);
        $this->service->create(2, 'TASK_CHANGES', 'Task', 1);
        $this->service->create(1, 'PROJECT_CHANGES', 'Project', 1);

        $this->assertSame(3, Scheduling::count());
    }
}
