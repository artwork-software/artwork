<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\MoneySource\Models\MoneySource;
use Artwork\Modules\MoneySource\Models\MoneySourceTask;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * FIN-01: Erledigen dürfen Zugewiesene oder wer die Quelle bearbeiten darf;
 * Anlegen/Löschen nur mit Bearbeitungsrecht an der Quelle; Liste nur mit Leserecht.
 */
final class MoneySourceTaskControllerTest extends FeatureTestCase
{
    private function memberOf(MoneySource $source, bool $writeAccess = true, bool $competent = false): User
    {
        $user = User::factory()->create();
        $source->users()->attach($user->id, ['write_access' => $writeAccess, 'competent' => $competent]);

        return $user;
    }

    #[Test]
    public function guest_cannot_store_task(): void
    {
        $this->post(route('money_source.task.add'), [
            'money_source' => 1,
            'name' => 'Foo',
            'deadline' => '2025-01-01',
        ])->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_store_task_and_creator_is_the_authenticated_user(): void
    {
        $admin = $this->actingAsAdmin();
        $source = MoneySource::factory()->create();

        $response = $this->post(route('money_source.task.add'), [
            'money_source' => $source->id,
            'name' => 'My Task',
            'description' => 'Desc',
            'deadline' => '2025-12-31',
        ]);

        $this->assertContains($response->status(), [200, 302]);
        $this->assertDatabaseHas('money_source_tasks', [
            'money_source_id' => $source->id,
            'name' => 'My Task',
            'creator' => $admin->id,
        ]);
    }

    #[Test]
    public function store_validates_required_fields(): void
    {
        $this->actingAsAdmin();

        $this->from(route('tasks.own'))->post(route('money_source.task.add'), [
            'money_source' => 999999,
            'name' => '',
            'deadline' => 'not-a-date',
        ])->assertSessionHasErrors(['money_source', 'name', 'deadline']);
    }

    #[Test]
    public function store_assigns_selected_users_or_falls_back_to_competent_users(): void
    {
        $this->actingAsAdmin();
        $source = MoneySource::factory()->create();
        $competent = $this->memberOf($source, writeAccess: false, competent: true);
        $selected = User::factory()->create();

        $this->post(route('money_source.task.add'), [
            'money_source' => $source->id,
            'name' => 'Mit Auswahl',
            'users' => [$selected->id],
        ]);
        $withSelection = MoneySourceTask::query()->where('name', 'Mit Auswahl')->firstOrFail();
        $this->assertSame([$selected->id], $withSelection->money_source_task_users()->pluck('users.id')->all());

        $this->post(route('money_source.task.add'), [
            'money_source' => $source->id,
            'name' => 'Ohne Auswahl',
        ]);
        $withoutSelection = MoneySourceTask::query()->where('name', 'Ohne Auswahl')->firstOrFail();
        $this->assertSame([$competent->id], $withoutSelection->money_source_task_users()->pluck('users.id')->all());
    }

    #[Test]
    public function user_with_edit_right_on_the_source_can_store_task(): void
    {
        $source = MoneySource::factory()->create();
        $editor = $this->memberOf($source);
        $this->actingAs($editor);

        $response = $this->post(route('money_source.task.add'), [
            'money_source' => $source->id,
            'name' => 'Editor Task',
        ]);

        $this->assertContains($response->status(), [200, 302]);
        $this->assertDatabaseHas('money_source_tasks', [
            'money_source_id' => $source->id,
            'name' => 'Editor Task',
            'creator' => $editor->id,
        ]);
    }

    #[Test]
    public function stranger_without_rights_cannot_store_task(): void
    {
        $source = MoneySource::factory()->create();
        $this->actingAs(User::factory()->create());

        $this->post(route('money_source.task.add'), [
            'money_source' => $source->id,
            'name' => 'Nope',
        ])->assertForbidden();

        $this->assertDatabaseMissing('money_source_tasks', ['name' => 'Nope']);
    }

    #[Test]
    public function admin_can_mark_task_done(): void
    {
        $this->actingAsAdmin();
        $task = MoneySourceTask::factory()->create(['done' => false]);

        $response = $this->patch(route('money_source.task.done', $task));

        $this->assertContains($response->status(), [200, 302]);
        $this->assertDatabaseHas('money_source_tasks', ['id' => $task->id, 'done' => 1]);
    }

    #[Test]
    public function admin_can_mark_task_undone(): void
    {
        $this->actingAsAdmin();
        $task = MoneySourceTask::factory()->create(['done' => true]);

        $response = $this->patch(route('money_source.task.undone', $task));

        $this->assertContains($response->status(), [200, 302]);
        $this->assertDatabaseHas('money_source_tasks', ['id' => $task->id, 'done' => 0]);
    }

    #[Test]
    public function assignee_without_source_rights_can_complete_and_reopen(): void
    {
        $task = MoneySourceTask::factory()->create(['done' => false]);
        $assignee = User::factory()->create();
        $task->money_source_task_users()->attach($assignee->id);
        $this->actingAs($assignee);

        $done = $this->patch(route('money_source.task.done', $task));
        $this->assertContains($done->status(), [200, 302]);
        $this->assertDatabaseHas('money_source_tasks', ['id' => $task->id, 'done' => 1]);

        $undone = $this->patch(route('money_source.task.undone', $task));
        $this->assertContains($undone->status(), [200, 302]);
        $this->assertDatabaseHas('money_source_tasks', ['id' => $task->id, 'done' => 0]);
    }

    #[Test]
    public function stranger_without_rights_cannot_complete(): void
    {
        $task = MoneySourceTask::factory()->create(['done' => false]);
        $this->actingAs(User::factory()->create());

        $this->patch(route('money_source.task.done', $task))->assertForbidden();
        $this->patch(route('money_source.task.undone', $task))->assertForbidden();
        $this->assertDatabaseHas('money_source_tasks', ['id' => $task->id, 'done' => 0]);
    }

    #[Test]
    public function user_with_edit_right_on_the_source_can_complete_unassigned_task(): void
    {
        $source = MoneySource::factory()->create();
        $task = MoneySourceTask::factory()->create(['money_source_id' => $source->id, 'done' => false]);
        $this->actingAs($this->memberOf($source));

        $response = $this->patch(route('money_source.task.done', $task));

        $this->assertContains($response->status(), [200, 302]);
        $this->assertDatabaseHas('money_source_tasks', ['id' => $task->id, 'done' => 1]);
    }

    #[Test]
    public function only_users_with_edit_right_on_the_source_can_delete(): void
    {
        $source = MoneySource::factory()->create();
        $task = MoneySourceTask::factory()->create(['money_source_id' => $source->id]);
        $assignee = User::factory()->create();
        $task->money_source_task_users()->attach($assignee->id);

        // Zugewiesen ≠ löschen dürfen
        $this->actingAs($assignee);
        $this->delete(route('money_source.task.destroy', $task))->assertForbidden();
        $this->assertDatabaseHas('money_source_tasks', ['id' => $task->id]);

        $this->actingAs(User::factory()->create());
        $this->delete(route('money_source.task.destroy', $task))->assertForbidden();

        $this->actingAs($this->memberOf($source));
        $response = $this->delete(route('money_source.task.destroy', $task));
        $this->assertContains($response->status(), [200, 302]);
        $this->assertDatabaseMissing('money_source_tasks', ['id' => $task->id]);
        $this->assertDatabaseMissing('money_source_task_user', ['task_id' => $task->id]);
    }

    #[Test]
    public function index_requires_view_right_on_the_source(): void
    {
        $source = MoneySource::factory()->create();
        MoneySourceTask::factory()->create(['money_source_id' => $source->id, 'name' => 'Sichtbar']);

        $this->actingAs(User::factory()->create());
        $this->getJson(route('money_source.task.index', ['money_source_id' => $source->id]))
            ->assertForbidden();

        $this->actingAs($this->memberOf($source, writeAccess: false, competent: true));
        $this->getJson(route('money_source.task.index', ['money_source_id' => $source->id]))
            ->assertOk()
            ->assertJsonFragment(['name' => 'Sichtbar']);
    }
}
