<?php

namespace Tests\Feature\Modules\ExternalIssue;

use Artwork\Modules\ExternalIssue\Models\ExternalIssue;
use Artwork\Modules\InternalIssue\Models\InternalIssue;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Services\ProjectTabMaterialIssueService;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ExternalIssueProjectLinkTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        // Materialausgaben verlangen seit 02.09.2026 "Inventardisposition" (MaterialIssuePolicy)
        $this->user = $this->actingAsUserWith(PermissionEnum::INVENTORY_DISPOSITION->value);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Externe Ausgabe',
            'material_value' => 100,
            'issue_date' => '2026-08-01',
            'return_date' => '2026-08-10',
            'external_name' => 'Externe Person',
        ], $overrides);
    }

    #[Test]
    public function store_persists_project_link(): void
    {
        $project = Project::factory()->create();

        $this->post(route('extern-issue-of-material.store'), $this->validPayload([
            'project_id' => $project->id,
        ]))->assertRedirect();

        $this->assertDatabaseHas('external_issues', [
            'name' => 'Externe Ausgabe',
            'project_id' => $project->id,
        ]);
    }

    #[Test]
    public function store_rejects_unknown_project(): void
    {
        $this->from(route('extern-issue-of-material.index'))
            ->post(route('extern-issue-of-material.store'), $this->validPayload([
                'project_id' => 999999,
            ]))
            ->assertSessionHasErrors('project_id');

        $this->assertDatabaseMissing('external_issues', ['name' => 'Externe Ausgabe']);
    }

    #[Test]
    public function update_can_set_and_clear_project_link(): void
    {
        $project = Project::factory()->create();
        $issue = ExternalIssue::factory()->create(['return_date' => '2026-08-10']);

        $this->patch(
            route('extern-issue-of-material.update', $issue),
            $this->validPayload(['project_id' => $project->id])
        )->assertRedirect();
        $this->assertSame($project->id, $issue->fresh()->project_id);

        $this->patch(
            route('extern-issue-of-material.update', $issue),
            $this->validPayload(['project_id' => null])
        )->assertRedirect();
        $this->assertNull($issue->fresh()->project_id);
    }

    #[Test]
    public function project_tab_payload_contains_internal_and_external_issues_of_the_project_only(): void
    {
        $project = Project::factory()->create();
        $otherProject = Project::factory()->create();

        $internal = InternalIssue::factory()->create(['project_id' => $project->id]);
        $external = ExternalIssue::factory()->create(['project_id' => $project->id]);
        $foreignExternal = ExternalIssue::factory()->create(['project_id' => $otherProject->id]);
        $unlinkedExternal = ExternalIssue::factory()->create(['project_id' => null]);

        $payload = app(ProjectTabMaterialIssueService::class)->buildMaterialIssuePayload($project);

        $this->assertTrue($payload['materials']->contains('id', $internal->id));
        $this->assertTrue($payload['externalMaterials']->contains('id', $external->id));
        $this->assertFalse($payload['externalMaterials']->contains('id', $foreignExternal->id));
        $this->assertFalse($payload['externalMaterials']->contains('id', $unlinkedExternal->id));
    }
}
