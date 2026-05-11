<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Task\Models\Task;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class TaskControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_view_task_create_page(): void
    {
        $this->get(route('tasks.create'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_view_task_create_page(): void
    {
        $this->actingAsAdmin();

        $this->get(route('tasks.create'))->assertOk();
    }

    #[Test]
    public function guest_cannot_view_own_tasks(): void
    {
        $this->get(route('tasks.own'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_view_own_tasks(): void
    {
        $this->actingAsAdmin();

        $this->get(route('tasks.own'))->assertOk();
    }

    #[Test]
    public function guest_cannot_store_task(): void
    {
        $checklist = Checklist::factory()->create();

        $this->post(route('tasks.store'), [
            'name' => 'Test',
            'checklist_id' => $checklist->id,
        ])->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_store_task(): void
    {
        $this->actingAsAdmin();
        $checklist = Checklist::factory()->create();

        $response = $this->post(route('tasks.store'), [
            'name' => 'My Task',
            'description' => 'A description',
            'checklist_id' => $checklist->id,
            'deadlineDate' => '2026-01-01',
            'users' => [],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tasks', [
            'name' => 'My Task',
            'checklist_id' => $checklist->id,
        ]);
    }

    #[Test]
    public function store_task_validates_required_name(): void
    {
        $this->actingAsAdmin();
        $checklist = Checklist::factory()->create();

        $response = $this->post(route('tasks.store'), [
            'description' => 'No name',
            'checklist_id' => $checklist->id,
        ]);

        $response->assertSessionHasErrors('name');
    }

    #[Test]
    public function store_task_validates_required_checklist_id(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('tasks.store'), [
            'name' => 'Has name',
        ]);

        $response->assertSessionHasErrors('checklist_id');
    }

    #[Test]
    public function store_task_validates_checklist_id_exists(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('tasks.store'), [
            'name' => 'Has name',
            'checklist_id' => 999999,
        ]);

        $response->assertSessionHasErrors('checklist_id');
    }

    #[Test]
    public function guest_cannot_view_task_edit_page(): void
    {
        $task = Task::factory()->create();

        $this->get('/tasks/' . $task->id . '/edit')
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_view_task_edit_page(): void
    {
        $this->actingAsAdmin();
        $task = Task::factory()->create();

        $this->get('/tasks/' . $task->id . '/edit')->assertOk();
    }

    #[Test]
    public function guest_cannot_update_task(): void
    {
        $task = Task::factory()->create();

        $this->patch(route('tasks.update', $task), [
            'id' => $task->id,
            'name' => 'New',
            'checklist_id' => $task->checklist_id,
        ])->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_update_task(): void
    {
        $this->actingAsAdmin();
        $task = Task::factory()->create(['name' => 'Old']);

        $response = $this->patch(route('tasks.update', $task), [
            'id' => $task->id,
            'name' => 'New Name',
            'checklist_id' => $task->checklist_id,
            'users' => [],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'name' => 'New Name',
        ]);
    }

    #[Test]
    public function update_task_validates_required_name(): void
    {
        $this->actingAsAdmin();
        $task = Task::factory()->create();

        $response = $this->patch(route('tasks.update', $task), [
            'id' => $task->id,
            'checklist_id' => $task->checklist_id,
        ]);

        $response->assertSessionHasErrors('name');
    }

    #[Test]
    public function guest_cannot_destroy_task(): void
    {
        $task = Task::factory()->create();

        $this->delete(route('tasks.destroy', $task))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_destroy_task(): void
    {
        $this->actingAsAdmin();
        $task = Task::factory()->create();

        $response = $this->delete(route('tasks.destroy', $task));

        $response->assertRedirect();
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    #[Test]
    public function guest_cannot_toggle_done_status(): void
    {
        $task = Task::factory()->create(['done' => false]);

        $this->patch(route('tasks.done', $task))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_toggle_done_status(): void
    {
        $this->actingAsAdmin();
        $task = Task::factory()->create(['done' => false]);

        $response = $this->patch(route('tasks.done', $task));

        $response->assertRedirect();
        $this->assertTrue((bool) $task->fresh()->done);
    }

    #[Test]
    public function guest_cannot_update_task_order(): void
    {
        $task = Task::factory()->create();

        $this->put(route('tasks.order'), [
            'checklistTasks' => [
                ['id' => $task->id, 'order' => 5],
            ],
        ])->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_update_task_order(): void
    {
        $this->actingAsAdmin();
        $task1 = Task::factory()->create(['order' => 1]);
        $task2 = Task::factory()->create(['order' => 2]);

        $response = $this->put(route('tasks.order'), [
            'checklistTasks' => [
                ['id' => $task1->id, 'order' => 2],
                ['id' => $task2->id, 'order' => 1],
            ],
        ]);

        $response->assertRedirect();
        $this->assertSame(2, (int) $task1->fresh()->order);
        $this->assertSame(1, (int) $task2->fresh()->order);
    }

    #[Test]
    public function update_order_validates_checklist_tasks_required(): void
    {
        $this->actingAsAdmin();

        $response = $this->put(route('tasks.order'), []);

        $response->assertSessionHasErrors('checklistTasks');
    }

    #[Test]
    public function admin_can_change_task_checklist(): void
    {
        $this->actingAsAdmin();
        $task = Task::factory()->create();
        $newChecklist = Checklist::factory()->create();

        $this->patch('/checklists/' . $newChecklist->id . '/change/task/' . $task->id);

        $this->assertSame($newChecklist->id, (int) $task->fresh()->checklist_id);
    }
}
