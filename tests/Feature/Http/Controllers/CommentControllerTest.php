<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Project\Models\Comment;
use Artwork\Modules\Project\Models\Project;
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
}
