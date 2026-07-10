<?php

namespace Tests\Unit\Modules\Project\Services;

use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectRole;
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

    #[Test]
    public function team_payload_separates_displayed_members_from_email_recipients(): void
    {
        $creator = User::factory()->create();
        $manager = User::factory()->create();
        $roleMember = User::factory()->create();
        $teamMember = User::factory()->create();
        $role = ProjectRole::factory()->create();
        $project = Project::factory()->create(['user_id' => $creator->id]);

        $project->users()->attach([
            $creator->id => ['roles' => []],
            $manager->id => ['is_manager' => true, 'roles' => []],
            $roleMember->id => ['roles' => [$role->id]],
            $teamMember->id => ['roles' => []],
        ]);

        $payload = $this->service->buildTeamPayload($project);

        $displayedMemberIds = collect($payload['project']['projectTeamMembers'])->pluck('id');
        $emailRecipients = collect($payload['project']['usersArray'])->keyBy('id');

        $this->assertSame([$teamMember->id], $displayedMemberIds->all());
        $this->assertEqualsCanonicalizing(
            [$creator->id, $manager->id, $roleMember->id, $teamMember->id],
            $emailRecipients->keys()->all()
        );
        $this->assertSame($creator->email, $emailRecipients[$creator->id]['email']);
        $this->assertSame($manager->email, $emailRecipients[$manager->id]['email']);
        $this->assertSame($roleMember->email, $emailRecipients[$roleMember->id]['email']);
        $this->assertSame($teamMember->email, $emailRecipients[$teamMember->id]['email']);
    }
}
