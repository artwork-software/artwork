<?php

namespace Tests\Unit\Modules\MoneySource\Services;

use Artwork\Modules\MoneySource\Models\MoneySourceTask;
use Artwork\Modules\MoneySource\Services\MoneySourceTaskService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class MoneySourceTaskServiceTest extends TestCase
{
    private MoneySourceTaskService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(MoneySourceTaskService::class);
    }

    #[Test]
    public function mark_as_done_sets_done_true(): void
    {
        $task = MoneySourceTask::factory()->create(['done' => false]);

        $this->service->markAsDone($task);

        $this->assertTrue((bool) $task->fresh()->done);
        $this->assertDatabaseHas('money_source_tasks', [
            'id' => $task->id,
            'done' => true,
        ]);
    }
}
