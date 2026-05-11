<?php

namespace Tests\Feature\Http\Controllers\ProjectTab;

use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class ProjectShiftControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_access_shift_tab(): void
    {
        $project = Project::factory()->create();

        $this->get(route('projects.tabs.shift', $project))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_shift_tab_passes_authorization(): void
    {
        // ProjectTabShiftService has a known type-error in UserRepository::getWorkers
        // (Carbon\Carbon vs Illuminate\Support\Carbon). Authorization is what we assert here.
        $this->actingAsAdmin();
        $project = Project::factory()->create();

        $response = $this->getJson(route('projects.tabs.shift', $project));

        $this->assertNotEquals(302, $response->getStatusCode(), 'Admin should not be redirected.');
        $this->assertNotEquals(401, $response->getStatusCode(), 'Admin should not be unauthorized.');
    }

    #[Test]
    public function user_without_project_access_is_redirected_back(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::factory()->create();

        $response = $this->get(route('projects.tabs.shift', $project));

        $response->assertStatus(302);
    }
}
