<?php

namespace Tests\Unit\Modules\TaskTemplate\Services;

use Artwork\Modules\Checklist\Models\ChecklistTemplate;
use Artwork\Modules\TaskTemplate\Models\TaskTemplate;
use Artwork\Modules\TaskTemplate\Services\TaskTemplateService;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class TaskTemplateServiceTest extends TestCase
{
    private TaskTemplateService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TaskTemplateService::class);
    }

    #[Test]
    public function duplicate_task_templates_creates_copies_under_new_template(): void
    {
        $source = ChecklistTemplate::factory()->create();
        $target = ChecklistTemplate::factory()->create();
        $user = User::factory()->create();

        $task1 = TaskTemplate::factory()->create([
            'name' => 'Task One',
            'checklist_template_id' => $source->id,
        ]);
        $task2 = TaskTemplate::factory()->create([
            'name' => 'Task Two',
            'checklist_template_id' => $source->id,
        ]);
        $task1->task_users()->attach($user->id);

        $source->load('task_templates.task_users');

        $this->service->duplicateTaskTemplates($source, $target);

        $this->assertSame(2, $target->task_templates()->count());
        $this->assertDatabaseHas('task_templates', [
            'name' => 'Task One',
            'checklist_template_id' => $target->id,
        ]);
        $this->assertDatabaseHas('task_templates', [
            'name' => 'Task Two',
            'checklist_template_id' => $target->id,
        ]);

        // the duplicated task with user should have the same pivot record
        $duplicated = TaskTemplate::where('name', 'Task One')
            ->where('checklist_template_id', $target->id)
            ->first();
        $this->assertTrue($duplicated->task_users()->where('user_id', $user->id)->exists());
    }

    #[Test]
    public function duplicate_task_templates_handles_empty_source(): void
    {
        $source = ChecklistTemplate::factory()->create();
        $target = ChecklistTemplate::factory()->create();
        $source->load('task_templates');

        $this->service->duplicateTaskTemplates($source, $target);

        $this->assertSame(0, $target->task_templates()->count());
    }
}
