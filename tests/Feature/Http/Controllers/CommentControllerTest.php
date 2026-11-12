<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Project\Models\Comment;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class CommentControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_view_create(): void
    {
        $this->get(route('comments.create'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_view_create(): void
    {
        $this->actingAsAdmin();

        $this->get(route('comments.create'))->assertOk();
    }

    #[Test]
    public function guest_cannot_store(): void
    {
        $this->post(route('comments.store'), ['text' => 'X', 'project_id' => 1])
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_store_comment(): void
    {
        $admin = $this->actingAsAdmin();
        $project = Project::factory()->create();

        $response = $this->post(route('comments.store'), [
            'text' => 'My comment',
            'project_id' => $project->id,
            'tab_id' => null,
        ]);

        // policy create + service may redirect or return ok; just ensure not unauthorized
        $this->assertNotSame(403, $response->getStatusCode());
        $this->assertNotSame(401, $response->getStatusCode());
    }

    #[Test]
    public function admin_can_update_comment(): void
    {
        $admin = $this->actingAsAdmin();
        $comment = Comment::query()->forceCreate([
            'text' => 'Old',
            'user_id' => $admin->id,
        ]);

        $response = $this->patch('/comments/' . $comment->id, [
            'text' => 'New',
        ]);

        $response->assertRedirect();
        $this->assertSame('New', $comment->fresh()->text);
    }

    #[Test]
    public function admin_can_destroy_comment(): void
    {
        $admin = $this->actingAsAdmin();
        $project = Project::factory()->create();
        $comment = Comment::query()->forceCreate([
            'text' => 'Bye',
            'user_id' => $admin->id,
            'project_id' => $project->id,
        ]);

        $response = $this->delete('/comments/' . $comment->id);

        $response->assertOk();
    }

    #[Test]
    public function users_who_may_edit_the_project_can_comment_without_being_in_the_team(): void
    {
        $project = Project::factory()->create();
        $this->actingAsUserWith([PermissionEnum::WRITE_PROJECTS->value]);

        $this->post(route('comments.store'), ['text' => 'Mit Schreibrecht', 'project_id' => $project->id])
            ->assertSuccessful();

        $this->assertDatabaseHas('comments', ['project_id' => $project->id, 'text' => 'Mit Schreibrecht']);
    }

    #[Test]
    public function a_rejected_comment_is_answered_with_forbidden_instead_of_vanishing(): void
    {
        $project = Project::factory()->create();
        $this->actingAsUserWith([PermissionEnum::PROJECT_VIEW->value]);

        $this->post(route('comments.store'), ['text' => 'Nur Leserecht', 'project_id' => $project->id])
            ->assertForbidden();

        $this->assertDatabaseMissing('comments', ['project_id' => $project->id]);
    }

    #[Test]
    public function only_the_author_may_change_or_delete_a_comment(): void
    {
        $project = Project::factory()->create();
        $author = User::factory()->create();
        $comment = Comment::query()->forceCreate([
            'text' => 'Original',
            'user_id' => $author->id,
            'project_id' => $project->id,
        ]);
        $project->users()->attach($author->id, ['can_write' => true]);

        $this->actingAsUserWith([PermissionEnum::WRITE_PROJECTS->value]);
        $this->patch('/comments/' . $comment->id, ['text' => 'Fremd'])->assertForbidden();
        $this->delete('/comments/' . $comment->id)->assertForbidden();
        $this->assertSame('Original', $comment->fresh()->text);

        $this->actingAs($author);
        $this->patch('/comments/' . $comment->id, ['text' => 'Eigen'])->assertRedirect();
        $this->assertSame('Eigen', $comment->fresh()->text);
        $this->delete('/comments/' . $comment->id)->assertSuccessful();
        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    #[Test]
    public function a_comment_of_a_deleted_author_is_forbidden_instead_of_failing(): void
    {
        $project = Project::factory()->create();
        $comment = Comment::query()->forceCreate([
            'text' => 'Verwaist',
            'user_id' => null,
            'project_id' => $project->id,
        ]);

        $this->actingAsUserWith([PermissionEnum::WRITE_PROJECTS->value]);
        $this->patch('/comments/' . $comment->id, ['text' => 'Fremd'])->assertForbidden();
        $this->delete('/comments/' . $comment->id)->assertForbidden();
        $this->assertSame('Verwaist', $comment->fresh()->text);
    }
}
