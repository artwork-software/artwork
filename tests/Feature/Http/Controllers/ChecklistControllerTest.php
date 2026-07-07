<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Task\Models\Task;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class ChecklistControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_view_checklist_create_page(): void
    {
        $this->get(route('checklists.create'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_view_checklist_create_page(): void
    {
        $this->actingAsAdmin();

        $this->get(route('checklists.create'))->assertOk();
    }

    #[Test]
    public function guest_cannot_store_checklist(): void
    {
        $this->post(route('checklists.store'), [
            'name' => 'My Checklist',
        ])->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_store_standalone_checklist(): void
    {
        $admin = $this->actingAsAdmin();

        $response = $this->post(route('checklists.store'), [
            'name' => 'My Checklist',
            'private' => false,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('checklists', [
            'name' => 'My Checklist',
            'user_id' => $admin->id,
        ]);
    }

    #[Test]
    public function admin_can_store_project_checklist_with_tasks(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();

        $response = $this->post(route('checklists.store'), [
            'name' => 'Project Checklist',
            'project_id' => $project->id,
            'private' => false,
            'tasks' => [
                ['name' => 'Task A', 'description' => 'Do A', 'deadline_dt_local' => '2026-06-01'],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('checklists', [
            'name' => 'Project Checklist',
            'project_id' => $project->id,
        ]);
        $this->assertDatabaseHas('tasks', ['name' => 'Task A']);
    }

    #[Test]
    public function guest_cannot_view_checklist(): void
    {
        $checklist = Checklist::factory()->create();

        $this->get('/checklists/' . $checklist->id)
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_view_checklist(): void
    {
        $this->actingAsAdmin();
        $checklist = Checklist::factory()->create();

        $this->get('/checklists/' . $checklist->id)->assertOk();
    }

    #[Test]
    public function admin_with_checklist_settings_can_view_checklist(): void
    {
        // ChecklistPolicy::view starts with can(CHECKLIST_SETTINGS_ADMIN) — admin satisfies it.
        $this->actingAsAdmin();
        $checklist = Checklist::factory()->create();

        $this->get('/checklists/' . $checklist->id)->assertOk();
    }

    #[Test]
    public function admin_can_view_checklist_edit_page(): void
    {
        $this->actingAsAdmin();
        $checklist = Checklist::factory()->create();

        $this->get('/checklists/' . $checklist->id . '/edit')->assertOk();
    }

    #[Test]
    public function guest_cannot_update_checklist(): void
    {
        $checklist = Checklist::factory()->create();

        $this->patch(route('checklists.update', $checklist), ['name' => 'Updated'])
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_update_checklist_name(): void
    {
        $this->actingAsAdmin();
        $checklist = Checklist::factory()->create(['name' => 'Old']);

        $response = $this->patch(route('checklists.update', $checklist), [
            'name' => 'Updated Name',
        ]);

        $response->assertRedirect();
        $this->assertSame('Updated Name', $checklist->fresh()->name);
    }

    #[Test]
    public function guest_cannot_destroy_checklist(): void
    {
        $checklist = Checklist::factory()->create();

        $this->delete(route('checklist.destroy', $checklist))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_destroy_checklist(): void
    {
        $this->actingAsAdmin();
        $checklist = Checklist::factory()->create();
        Task::factory()->count(2)->create(['checklist_id' => $checklist->id]);

        $response = $this->delete(route('checklist.destroy', $checklist));

        $response->assertRedirect();
        $this->assertDatabaseMissing('checklists', ['id' => $checklist->id]);
        $this->assertDatabaseMissing('tasks', ['checklist_id' => $checklist->id]);
    }

    #[Test]
    public function user_without_checklist_permission_cannot_destroy_other_users_checklist(): void
    {
        $this->actingAs(User::factory()->create());
        $other = User::factory()->create();
        $checklist = Checklist::factory()->create([
            'user_id' => $other->id,
            'project_id' => null,
        ]);

        $response = $this->delete(route('checklist.destroy', $checklist));

        $response->assertForbidden();
        $this->assertDatabaseHas('checklists', ['id' => $checklist->id]);
    }

    #[Test]
    public function guest_cannot_duplicate_checklist(): void
    {
        $checklist = Checklist::factory()->create();

        $this->post(route('checklists.duplicate', $checklist))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_duplicate_checklist(): void
    {
        $this->actingAsAdmin();
        $checklist = Checklist::factory()->create(['name' => 'Original']);

        $before = Checklist::query()->count();

        $response = $this->post(route('checklists.duplicate', $checklist));

        $response->assertRedirect();
        $this->assertSame($before + 1, Checklist::query()->count());
    }

    #[Test]
    public function guest_cannot_toggle_done_all_tasks(): void
    {
        $checklist = Checklist::factory()->create();

        $this->patch('/checklists/' . $checklist->id . '/doneOrUndone/all/tasks', [
            'done' => true,
        ])->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_mark_all_tasks_as_done(): void
    {
        $this->actingAsAdmin();
        $checklist = Checklist::factory()->create();
        Task::factory()->count(2)->create([
            'checklist_id' => $checklist->id,
            'done' => false,
        ]);

        $response = $this->patch('/checklists/' . $checklist->id . '/doneOrUndone/all/tasks', [
            'done' => true,
        ]);

        $response->assertRedirect();
        foreach ($checklist->fresh()->tasks as $task) {
            $this->assertTrue((bool) $task->done);
        }
    }
}
