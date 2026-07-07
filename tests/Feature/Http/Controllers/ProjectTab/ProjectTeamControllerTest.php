<?php

namespace Tests\Feature\Http\Controllers\ProjectTab;

use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class ProjectTeamControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_access_team_tab(): void
    {
        $project = Project::factory()->create();

        $this->get(route('projects.tabs.team', $project))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_view_team_tab(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();

        $response = $this->getJson(route('projects.tabs.team', $project));

        $response->assertOk();
    }

    #[Test]
    public function user_without_project_access_is_redirected_back(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::factory()->create();

        $response = $this->get(route('projects.tabs.team', $project));

        $response->assertStatus(302);
    }
}
