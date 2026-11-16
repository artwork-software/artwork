<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Checklist\Models\ChecklistTemplate;
use Artwork\Modules\Project\Models\Comment;
use Artwork\Modules\Task\Models\Task;
use Artwork\Modules\TaskTemplate\Models\TaskTemplate;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Funktionstests für Checklisten-Vorlagen, Aufgabenvorlagen, "alle Aufgaben erledigt" und Kommentare.
 */
final class ChecklistTemplateTaskAndCommentActionsTest extends FeatureTestCase
{
    #[Test]
    public function duplicating_a_template_copies_users_and_task_templates_with_their_users(): void
    {
        $admin = $this->actingAsAdmin();
        $member = User::factory()->create();
        $template = ChecklistTemplate::factory()->create(['name' => 'Premiere']);
        $template->users()->sync([$member->id]);
        $taskTemplate = TaskTemplate::factory()->create([
            'checklist_template_id' => $template->id,
            'name' => 'Programmheft',
        ]);
        $taskTemplate->task_users()->sync([$member->id]);

        $this->from('/templates')
            ->post(route('checklist_templates.duplicate', $template))
            ->assertRedirect('/templates');

        $copy = ChecklistTemplate::query()->whereKeyNot($template->id)->where('name', 'like', 'Premiere%')->firstOrFail();
        $this->assertSame($admin->id, $copy->user_id);
        $this->assertSame([$member->id], $copy->users()->pluck('users.id')->all());
        $copiedTask = $copy->task_templates()->firstOrFail();
        $this->assertSame('Programmheft', $copiedTask->name);
        $this->assertSame([$member->id], $copiedTask->task_users()->pluck('users.id')->all());
        // Original bleibt unverändert
        $this->assertSame(1, $template->task_templates()->count());
    }

    #[Test]
    public function duplicating_a_template_with_a_maximum_length_name_does_not_fail(): void
    {
        $this->actingAsAdmin();
        $template = ChecklistTemplate::factory()->create(['name' => str_repeat('a', 255)]);

        $this->post(route('checklist_templates.duplicate', $template))->assertRedirect();

        $this->assertSame(2, ChecklistTemplate::query()->where('name', 'like', 'aaaa%')->count());
    }

    #[Test]
    public function updating_a_template_changes_name_and_syncs_users(): void
    {
        $this->actingAsAdmin();
        $oldMember = User::factory()->create();
        $newMember = User::factory()->create();
        $template = ChecklistTemplate::factory()->create(['name' => 'Alt']);
        $template->users()->sync([$oldMember->id]);
        $taskTemplate = TaskTemplate::factory()->create(['checklist_template_id' => $template->id]);

        $this->from('/templates')->patch(route('checklist_templates.update', $template), [
            'name' => 'Neu',
            'users' => [['id' => $newMember->id]],
        ])->assertRedirect('/templates');

        $template->refresh();
        $this->assertSame('Neu', $template->name);
        $this->assertSame([$newMember->id], $template->users()->pluck('users.id')->all());
        // ohne task_templates im Request bleiben die Aufgabenvorlagen bestehen
        $this->assertModelExists($taskTemplate);
    }

    #[Test]
    public function updating_a_template_with_task_templates_replaces_them_and_ignores_foreign_fields(): void
    {
        $this->actingAsAdmin();
        $member = User::factory()->create();
        $template = ChecklistTemplate::factory()->create();
        $otherTemplate = ChecklistTemplate::factory()->create();
        $oldTask = TaskTemplate::factory()->create(['checklist_template_id' => $template->id]);

        $this->patch(route('checklist_templates.update', $template), [
            'users' => [['id' => $member->id]],
            'task_templates' => [[
                'name' => 'Neue Aufgabe',
                'deadline_days_after_creation' => 3,
                'checklist_template_id' => $otherTemplate->id,
            ]],
        ])->assertRedirect();

        $this->assertModelMissing($oldTask);
        $newTask = $template->task_templates()->firstOrFail();
        $this->assertSame('Neue Aufgabe', $newTask->name);
        $this->assertSame(3, (int) $newTask->deadline_days_after_creation);
        $this->assertSame([$member->id], $newTask->task_users()->pluck('users.id')->all());
        $this->assertSame(0, $otherTemplate->task_templates()->count());
    }

    #[Test]
    public function updating_a_template_validates_input(): void
    {
        $this->actingAsAdmin();
        $template = ChecklistTemplate::factory()->create(['name' => 'Alt']);

        $this->patchJson(route('checklist_templates.update', $template), [
            'name' => '',
            'users' => [['id' => 99999999]],
            'task_templates' => [['name' => '', 'deadline_days_after_creation' => -1]],
        ])->assertUnprocessable()->assertJsonValidationErrors([
            'name',
            'users.0.id',
            'task_templates.0.name',
            'task_templates.0.deadline_days_after_creation',
        ]);

        $this->assertSame('Alt', $template->fresh()->name);
    }

    #[Test]
    public function task_template_order_is_updated_and_validated(): void
    {
        $this->actingAsAdmin();
        $template = ChecklistTemplate::factory()->create();
        $first = TaskTemplate::factory()->create(['checklist_template_id' => $template->id, 'order' => 1]);
        $second = TaskTemplate::factory()->create(['checklist_template_id' => $template->id, 'order' => 2]);

        $this->put(route('task_templates.order'), ['taskTemplates' => [
            ['id' => $first->id, 'order' => 2],
            ['id' => $second->id, 'order' => 1],
        ]])->assertRedirect();

        $this->assertSame(2, (int) $first->fresh()->order);
        $this->assertSame(1, (int) $second->fresh()->order);

        $this->putJson(route('task_templates.order'), ['taskTemplates' => [['id' => 99999999, 'order' => -1]]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['taskTemplates.0.id', 'taskTemplates.0.order']);
        $this->putJson(route('task_templates.order'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('taskTemplates');
    }

    #[Test]
    public function destroying_a_task_template_removes_only_that_template(): void
    {
        $this->actingAsAdmin();
        $template = ChecklistTemplate::factory()->create();
        $keep = TaskTemplate::factory()->create(['checklist_template_id' => $template->id]);
        $remove = TaskTemplate::factory()->create(['checklist_template_id' => $template->id]);

        $this->from('/templates')->delete(route('task_templates.destroy', $remove))->assertRedirect('/templates');

        $this->assertModelMissing($remove);
        $this->assertModelExists($keep);
    }

    #[Test]
    public function marking_all_tasks_as_undone_clears_completion_data(): void
    {
        $this->actingAsAdmin();
        $checklist = Checklist::factory()->create();
        $doneBy = User::factory()->create();
        $task = Task::factory()->create([
            'checklist_id' => $checklist->id,
            'done' => true,
            'user_id' => $doneBy->id,
            'done_at' => now()->subDay(),
        ]);

        $this->patch(route('checklists.doneOrUndone.all.tasks', $checklist), ['done' => false])->assertRedirect();

        $task->refresh();
        $this->assertFalse($task->done);
        $this->assertNull($task->user_id);
        $this->assertNull($task->done_at);
    }

    #[Test]
    public function marking_all_tasks_as_done_keeps_who_and_when_of_already_finished_tasks(): void
    {
        Carbon::setTestNow('2026-11-16 12:00:00');
        $admin = $this->actingAsAdmin();
        $checklist = Checklist::factory()->create();
        $earlierUser = User::factory()->create();
        $alreadyDone = Task::factory()->create([
            'checklist_id' => $checklist->id,
            'done' => true,
            'user_id' => $earlierUser->id,
            'done_at' => '2026-11-01 09:00:00',
        ]);
        $open = Task::factory()->create(['checklist_id' => $checklist->id, 'done' => false]);

        $this->patch(route('checklists.doneOrUndone.all.tasks', $checklist), ['done' => true])->assertRedirect();

        $alreadyDone->refresh();
        $this->assertSame($earlierUser->id, $alreadyDone->user_id);
        $this->assertSame('2026-11-01 09:00:00', $alreadyDone->done_at->toDateTimeString());

        $open->refresh();
        $this->assertTrue($open->done);
        $this->assertSame($admin->id, $open->user_id);
        $this->assertSame('2026-11-16 12:00:00', $open->done_at->toDateTimeString());
    }

    #[Test]
    public function marking_all_tasks_requires_a_boolean_done_flag(): void
    {
        $this->actingAsAdmin();
        $checklist = Checklist::factory()->create();
        $task = Task::factory()->create(['checklist_id' => $checklist->id, 'done' => false]);

        $this->patchJson(route('checklists.doneOrUndone.all.tasks', $checklist), ['done' => 'vielleicht'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('done');

        $this->assertFalse($task->fresh()->done);
    }

    #[Test]
    public function the_author_can_edit_and_delete_a_comment(): void
    {
        $author = $this->actingAsAdmin();
        $comment = Comment::factory()->create(['user_id' => $author->id, 'text' => 'Alt']);

        $this->from('/project')->patch('/comments/' . $comment->id, ['text' => 'Neu'])->assertRedirect('/project');
        $this->assertSame('Neu', $comment->fresh()->text);

        $this->patchJson('/comments/' . $comment->id, ['text' => ''])->assertUnprocessable();
        $this->patchJson('/comments/' . $comment->id, ['text' => str_repeat('x', 5001)])->assertUnprocessable();
        $this->assertSame('Neu', $comment->fresh()->text);

        $this->delete('/comments/' . $comment->id)->assertOk();
        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }
}
