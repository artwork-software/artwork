<?php

namespace Tests\Unit\Modules\Project\Services;

use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectState;
use Artwork\Modules\Project\Services\ProjectTabStatusService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ProjectTabStatusServiceTest extends TestCase
{
    private ProjectTabStatusService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ProjectTabStatusService();
    }

    #[Test]
    public function build_status_payload_returns_related_state(): void
    {
        $state = ProjectState::factory()->create();
        $project = Project::factory()->create(['state' => $state->id]);

        $payload = $this->service->buildStatusPayload($project);

        $this->assertArrayHasKey('state', $payload);
        $this->assertNotNull($payload['state']);
        $this->assertSame($state->id, $payload['state']->id);
    }

    #[Test]
    public function build_status_payload_returns_null_when_project_has_no_state(): void
    {
        $project = Project::factory()->create(['state' => null]);

        $payload = $this->service->buildStatusPayload($project);

        $this->assertNull($payload['state']);
    }
}
