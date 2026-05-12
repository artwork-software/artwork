<?php

namespace Tests\Feature\Http\Controllers\ProjectTab;

use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class ProjectChecklistControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_access_all_checklists_tab(): void
    {
        $project = Project::factory()->create();

        $this->get(route('projects.tabs.all-checklists', $project))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_view_all_checklists_tab(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();

        $response = $this->getJson(route('projects.tabs.all-checklists', $project));

        $response->assertOk();
    }

    #[Test]
    public function user_without_project_access_is_redirected_back_for_all_checklists(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::factory()->create();

        $response = $this->get(route('projects.tabs.all-checklists', $project));

        $response->assertStatus(302);
    }

    #[Test]
    public function admin_gets_not_found_for_unknown_component_in_checklists(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();

        // 999 is a non-existent componentInTab id -> NotFoundHttpException
        $response = $this->getJson(route('projects.tabs.checklists', [
            'project' => $project->id,
            'componentInTab' => 999999,
        ]));

        $response->assertNotFound();
    }
}
