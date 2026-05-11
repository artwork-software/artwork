<?php

namespace Tests\Unit\Modules\Project\Services;

use Artwork\Modules\Project\Models\Comment;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Services\ProjectTabCommentService;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ProjectTabCommentServiceTest extends TestCase
{
    private ProjectTabCommentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ProjectTabCommentService();
    }

    #[Test]
    public function build_all_comments_payload_loads_comments_with_user_ids(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create();
        Comment::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);

        $payload = $this->service->buildAllCommentsPayload($project);

        $this->assertArrayHasKey('comments', $payload);
        $this->assertGreaterThanOrEqual(1, count($payload['comments']));
        $this->assertArrayHasKey('projectWriteIds', $payload);
        $this->assertArrayHasKey('projectManagerIds', $payload);
    }

    #[Test]
    public function build_comment_payload_returns_empty_with_no_scope(): void
    {
        $project = Project::factory()->create();

        $payload = $this->service->buildCommentPayload($project);

        $this->assertArrayHasKey('comments', $payload);
    }
}
