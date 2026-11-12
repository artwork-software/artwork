<?php

namespace Tests\Unit\Modules\Project\Services;

use Artwork\Modules\Checklist\Models\Checklist;
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
        $this->service = app(ProjectTabChecklistService::class);
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

    #[Test]
    public function private_checklists_stay_hidden_from_the_project_team(): void
    {
        $owner = User::factory()->create();
        $teamMember = User::factory()->create();
        $project = Project::factory()->create();
        $project->users()->attach([$owner->id, $teamMember->id], ['can_write' => true]);
        $private = Checklist::factory()->create([
            'project_id' => $project->id,
            'user_id' => $owner->id,
            'private' => true,
        ]);

        $this->actingAs($teamMember);
        $this->assertSame([], $this->service->buildAllChecklistsPayload($project)['private_all_checklists']);
        $this->assertFalse($teamMember->can('view', $private));
        $this->assertFalse($teamMember->can('update', $private));
        $this->assertFalse($teamMember->can('delete', $private));

        $this->actingAs($owner);
        $this->assertSame(
            [$private->id],
            array_column($this->service->buildAllChecklistsPayload($project)['private_all_checklists'], 'id')
        );
        $this->assertTrue($owner->can('update', $private));
    }

    #[Test]
    public function public_checklists_stay_open_to_the_project_team(): void
    {
        $owner = User::factory()->create();
        $teamMember = User::factory()->create();
        $project = Project::factory()->create();
        $project->users()->attach($teamMember->id, ['can_write' => true]);
        $public = Checklist::factory()->create([
            'project_id' => $project->id,
            'user_id' => $owner->id,
            'private' => false,
        ]);

        $this->actingAs($teamMember);
        $this->assertTrue($teamMember->can('view', $public));
        $this->assertTrue($teamMember->can('update', $public));
    }
}
