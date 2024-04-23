<?php

namespace Tests\Unit\Artwork\Modules\Checklist\Services;

use Artwork\Modules\Checklist\Http\Requests\ChecklistUpdateRequest;
use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Checklist\Services\ChecklistService;
use Artwork\Modules\Tasks\Services\TaskService;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\TestCase;

class ChecklistServiceTest extends TestCase
{
    use WithoutMiddleware;

    protected ChecklistService $checklistService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->checklistService = app()->get(ChecklistService::class);
    }

    public function testUpdateByRequest(): void
    {
        $checklist = Checklist::factory()->create();
        $request = ChecklistUpdateRequest::create('/checklist', 'POST', [
            'tasks' => [
                ['name' => 'Task 1', 'description' => 'Description 1', 'done' => true, 'order' => 1],
                ['name' => 'Task 2', 'description' => 'Description 2', 'done' => true, 'order' => 2],
            ],
        ]);

        $this->checklistService->updateByRequest($checklist, $request, app()->make(TaskService::class));

        $this->assertDatabaseHas('checklists', [
            'id' => $checklist->id,
        ]);

        $this->assertDatabaseHas('tasks', [
            'name' => 'Task 1',
            'description' => 'Description 1',
            'checklist_id' => $checklist->id,
        ]);

        $this->assertDatabaseHas('tasks', [
            'name' => 'Task 2',
            'description' => 'Description 2',
            'checklist_id' => $checklist->id,
        ]);
    }
}
