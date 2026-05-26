<?php

namespace Tests\Unit\Modules\Project\Services;

use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Services\ProjectTabChecklistService;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ProjectTabChecklistServiceTest extends TestCase
{
    private ProjectTabChecklistService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ProjectTabChecklistService();
    }

    #[Test]
    public function build_checklist_payload_returns_expected_structure(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $project = Project::factory()->create();

        $payload = $this->service->buildChecklistPayload($project);

        $this->assertArrayHasKey('opened_checklists', $payload);
        $this->assertArrayHasKey('checklist_templates', $payload);
        $this->assertArrayHasKey('public_checklists', $payload);
        $this->assertArrayHasKey('private_checklists', $payload);
    }

    #[Test]
    public function build_all_checklists_payload_returns_expected_keys(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $project = Project::factory()->create();

        $payload = $this->service->buildAllChecklistsPayload($project);

        $this->assertArrayHasKey('opened_checklists', $payload);
        $this->assertArrayHasKey('public_all_checklists', $payload);
        $this->assertArrayHasKey('private_all_checklists', $payload);
    }
}
