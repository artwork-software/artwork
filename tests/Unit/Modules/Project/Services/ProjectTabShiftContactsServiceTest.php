<?php

namespace Tests\Unit\Modules\Project\Services;

use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Services\ProjectService;
use Artwork\Modules\Project\Services\ProjectTabShiftContactsService;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ProjectTabShiftContactsServiceTest extends TestCase
{
    private ProjectTabShiftContactsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ProjectTabShiftContactsService();
    }

    #[Test]
    public function build_shift_contacts_payload_exposes_managers_and_contacts(): void
    {
        $project = Project::factory()->create();
        $manager = User::factory()->create();
        app(ProjectService::class)->attachManagementUsers($project, [$manager->id]);

        $payload = $this->service->buildShiftContactsPayload($project);

        $this->assertArrayHasKey('shift_contacts', $payload);
        $this->assertArrayHasKey('project_managers', $payload);
        $this->assertTrue(collect($payload['project_managers'])->contains('id', $manager->id));
    }
}
