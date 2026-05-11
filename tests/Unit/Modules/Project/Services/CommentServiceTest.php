<?php

namespace Tests\Unit\Modules\Project\Services;

use Antonrom\ModelChangesHistory\Models\Change;
use Artwork\Modules\Change\Builders\ChangeBuilder;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Project\Models\Comment;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Services\CommentService;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class CommentServiceTest extends TestCase
{
    private CommentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CommentService::class);
    }

    #[Test]
    public function save_persists_comment_and_returns_it(): void
    {
        $comment = Comment::factory()->make();

        $result = $this->service->save($comment);

        $this->assertNotNull($result->id);
        $this->assertDatabaseHas('comments', ['id' => $result->id]);
    }

    #[Test]
    public function create_persists_comment_with_project_and_user(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create();
        $changeService = $this->mockChangeService();

        $comment = $this->service->create(
            'Hello world',
            $user,
            $changeService,
            $project,
            null,
            null,
            null,
            42
        );

        $this->assertSame($project->id, $comment->project_id);
        $this->assertSame($user->id, $comment->user_id);
        $this->assertSame(42, $comment->tab_id);
        $this->assertStringContainsString('Hello world', $comment->text);
    }

    #[Test]
    public function delete_soft_deletes_comment_via_repository(): void
    {
        $project = Project::factory()->create();
        $comment = Comment::factory()->create(['project_id' => $project->id]);
        $changeService = $this->mockChangeService();

        $this->service->delete($comment, $changeService);

        $this->assertSoftDeleted('comments', ['id' => $comment->id]);
    }

    #[Test]
    public function delete_all_soft_deletes_each_comment(): void
    {
        $project = Project::factory()->create();
        $comments = Comment::factory()->count(3)->create(['project_id' => $project->id]);
        $changeService = $this->mockChangeService();

        $this->service->deleteAll($comments, $changeService);

        foreach ($comments as $comment) {
            $this->assertSoftDeleted('comments', ['id' => $comment->id]);
        }
    }

    #[Test]
    public function restore_all_restores_soft_deleted_comments(): void
    {
        $project = Project::factory()->create();
        $comments = Comment::factory()->count(2)->create(['project_id' => $project->id]);
        foreach ($comments as $c) {
            $c->delete();
        }

        $this->service->restoreAll($comments);

        foreach ($comments as $comment) {
            $this->assertDatabaseHas('comments', ['id' => $comment->id, 'deleted_at' => null]);
        }
    }

    private function mockChangeService(): ChangeService
    {
        return $this->mock(ChangeService::class, function ($m) {
            $m->shouldReceive('createBuilder')->andReturn(ChangeBuilder::newInstance());
            $m->shouldReceive('saveFromBuilder')->andReturn(new Change());
        });
    }
}
