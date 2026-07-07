<?php

namespace Tests\Unit\Modules\Project\Services;

use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Services\ProjectService;
use Artwork\Modules\Project\Services\ProjectTabTeamService;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ProjectTabTeamServiceTest extends TestCase
{
    private ProjectTabTeamService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ProjectTabTeamService();
    }

    #[Test]
    public function build_team_payload_returns_top_level_keys(): void
    {
        $project = Project::factory()->create();
        $manager = User::factory()->create();
        app(ProjectService::class)->attachManagementUsers($project, [$manager->id]);

        $payload = $this->service->buildTeamPayload($project);

        $this->assertArrayHasKey('project', $payload);
        $this->assertArrayHasKey('projectManagerIds', $payload);
        $this->assertArrayHasKey('projectWriteIds', $payload);
        $this->assertSame($project->id, $payload['project']['id']);
        $this->assertTrue(collect($payload['project']['usersArray'])->contains('id', $manager->id));
    }
}
