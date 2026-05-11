<?php

namespace Tests\Unit\Modules\Task\Services;

use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Task\Models\Task;
use Artwork\Modules\Task\Services\TaskService;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class TaskServiceTest extends TestCase
{
    private TaskService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TaskService::class);
    }

    #[Test]
    public function create_new_task_returns_task_with_attributes(): void
    {
        $task = $this->service->createNewTask([
            'name' => 'Test Task',
            'description' => 'desc',
        ]);

        $this->assertInstanceOf(Task::class, $task);
        $this->assertSame('Test Task', $task->name);
        $this->assertFalse($task->exists);
    }

    #[Test]
    public function create_task_by_request_persists_task_with_checklist(): void
    {
        $checklist = Checklist::factory()->create();
        $author = User::factory()->create();
        $assignee = User::factory()->create();

        $task = $this->service->createTaskByRequest(
            $checklist,
            'My Task',
            $author->id,
            'desc',
            '2026-06-01',
            [$author->id, $assignee->id]
        );

        $this->assertTrue($task->exists);
        $this->assertSame('My Task', $task->name);
        $this->assertSame($checklist->id, $task->checklist_id);
        $this->assertNotNull($task->deadline);
        $this->assertFalse($task->done);

        // author was attached directly, assignee via syncWithDetach
        $this->assertTrue($task->task_users()->where('user_id', $author->id)->exists());
        $this->assertTrue($task->task_users()->where('user_id', $assignee->id)->exists());
    }

    #[Test]
    public function create_task_by_request_allows_null_deadline(): void
    {
        $checklist = Checklist::factory()->create();
        $user = User::factory()->create();

        $task = $this->service->createTaskByRequest(
            $checklist,
            'Foo',
            $user->id,
            null,
            null,
            [$user->id]
        );

        $this->assertNull($task->deadline);
    }

    #[Test]
    public function get_by_checklist_returns_tasks(): void
    {
        $checklist = Checklist::factory()->create();
        Task::factory()->count(2)->create(['checklist_id' => $checklist->id]);
        Task::factory()->create(); // unrelated

        $tasks = $this->service->getByChecklist($checklist);

        $this->assertInstanceOf(Collection::class, $tasks);
        $this->assertCount(2, $tasks);
    }

    #[Test]
    public function delete_by_checklist_removes_all_tasks(): void
    {
        $checklist = Checklist::factory()->create();
        Task::factory()->count(3)->create(['checklist_id' => $checklist->id]);

        $this->service->deleteByChecklist($checklist);

        $this->assertSame(0, Task::where('checklist_id', $checklist->id)->count());
    }

    #[Test]
    public function sync_task_users_with_detach_replaces_users(): void
    {
        $task = Task::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $task->task_users()->attach($user1->id);

        $this->service->syncTaskUsersWithDetach($task, [$user2->id]);

        $this->assertFalse($task->task_users()->where('user_id', $user1->id)->exists());
        $this->assertTrue($task->task_users()->where('user_id', $user2->id)->exists());
    }

    #[Test]
    public function delete_all_soft_deletes_tasks(): void
    {
        $tasks = Task::factory()->count(2)->create();

        $this->service->deleteAll($tasks);

        foreach ($tasks as $task) {
            $this->assertSoftDeleted('tasks', ['id' => $task->id]);
        }
    }

    #[Test]
    public function restore_all_restores_soft_deleted_tasks(): void
    {
        $tasks = Task::factory()->count(2)->create();
        foreach ($tasks as $task) {
            $task->delete();
        }

        $this->service->restoreAll(Task::withTrashed()->whereIn('id', $tasks->pluck('id'))->get());

        $this->assertSame(2, Task::whereIn('id', $tasks->pluck('id'))->count());
    }

    #[Test]
    public function force_delete_all_removes_tasks_permanently(): void
    {
        $tasks = Task::factory()->count(2)->create();

        $this->service->forceDeleteAll($tasks);

        $this->assertSame(0, Task::withTrashed()->whereIn('id', $tasks->pluck('id'))->count());
    }

    #[Test]
    public function restore_restores_single_task(): void
    {
        $task = Task::factory()->create();
        $task->delete();

        $this->service->restore(Task::withTrashed()->find($task->id));

        $this->assertNotSoftDeleted('tasks', ['id' => $task->id]);
    }

    #[Test]
    public function done_or_undone_all_tasks_sets_done_flag(): void
    {
        $checklist = Checklist::factory()->create();
        $user = User::factory()->create();
        Task::factory()->count(2)->create(['checklist_id' => $checklist->id, 'done' => false]);

        $tasks = $this->service->doneOrUndoneAllTasks($checklist, true, $user->id);

        $this->assertCount(2, $tasks);
        foreach (Task::where('checklist_id', $checklist->id)->get() as $task) {
            $this->assertTrue($task->done);
            $this->assertSame($user->id, $task->user_id);
            $this->assertNotNull($task->done_at);
        }
    }

    #[Test]
    public function done_or_undone_all_tasks_unsets_when_done_false(): void
    {
        $checklist = Checklist::factory()->create();
        $user = User::factory()->create();
        Task::factory()->create([
            'checklist_id' => $checklist->id,
            'done' => true,
            'user_id' => $user->id,
            'done_at' => Carbon::now(),
        ]);

        $this->service->doneOrUndoneAllTasks($checklist, false, $user->id);

        $task = Task::where('checklist_id', $checklist->id)->first();
        $this->assertFalse($task->done);
        $this->assertNull($task->user_id);
        $this->assertNull($task->done_at);
    }

    #[Test]
    public function done_or_undone_task_toggles_state(): void
    {
        $task = Task::factory()->create(['done' => false, 'done_at' => null, 'user_id' => null]);
        $user = User::factory()->create();

        $afterToggle = $this->service->doneOrUndoneTask($task, $user->id);

        $this->assertTrue($afterToggle->done);
        $this->assertSame($user->id, $afterToggle->user_id);
        $this->assertNotNull($afterToggle->done_at);

        $afterSecondToggle = $this->service->doneOrUndoneTask($afterToggle->fresh(), $user->id);
        $this->assertFalse($afterSecondToggle->done);
        $this->assertNull($afterSecondToggle->user_id);
        $this->assertNull($afterSecondToggle->done_at);
    }

    #[Test]
    public function duplicate_tasks_by_checklist_copies_tasks_and_users(): void
    {
        $source = Checklist::factory()->create();
        $target = Checklist::factory()->create();
        $user = User::factory()->create();

        $task = Task::factory()->create(['checklist_id' => $source->id]);
        $task->task_users()->attach($user->id);

        $this->service->duplicateTasksByChecklist($source, $target);

        $this->assertSame(1, Task::where('checklist_id', $target->id)->count());
        $copied = Task::where('checklist_id', $target->id)->first();
        $this->assertTrue($copied->task_users()->where('user_id', $user->id)->exists());
    }

    #[Test]
    public function reorder_tasks_updates_order_and_returns_last_task(): void
    {
        $checklist = Checklist::factory()->create();
        $task1 = Task::factory()->create(['checklist_id' => $checklist->id, 'order' => 1]);
        $task2 = Task::factory()->create(['checklist_id' => $checklist->id, 'order' => 2]);

        $payload = new SupportCollection([
            ['id' => $task1->id, 'order' => 10],
            ['id' => $task2->id, 'order' => 20],
        ]);

        $result = $this->service->reorderTasks($payload);

        $this->assertSame(20, (int) $task2->fresh()->order);
        $this->assertSame(10, (int) $task1->fresh()->order);
        $this->assertSame($task2->id, $result->id);
    }

    #[Test]
    public function update_by_request_updates_task_attributes(): void
    {
        $checklist = Checklist::factory()->create();
        $task = Task::factory()->create([
            'checklist_id' => $checklist->id,
            'name' => 'old',
            'done' => true,
        ]);
        $user = User::factory()->create();

        $data = new SupportCollection([
            'name' => 'new name',
            'description' => 'new desc',
            'deadlineDate' => '2026-06-15',
            'users' => [$user->id],
        ]);

        $updated = $this->service->updateByRequest($task, $data);

        $this->assertSame('new name', $updated->name);
        $this->assertSame('new desc', $updated->description);
        $this->assertFalse($updated->done);
        $this->assertNotNull($updated->deadline);
        $this->assertTrue($updated->task_users()->where('user_id', $user->id)->exists());
    }

    #[Test]
    public function sync_task_users_replaces_users(): void
    {
        $task = Task::factory()->create();
        $existing = User::factory()->create();
        $task->task_users()->attach($existing->id);

        $new = User::factory()->create();
        $this->service->syncTaskUsers($task, [$new->id]);

        $this->assertFalse($task->task_users()->where('user_id', $existing->id)->exists());
        $this->assertTrue($task->task_users()->where('user_id', $new->id)->exists());
    }
}
