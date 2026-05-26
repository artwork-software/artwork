<?php

namespace Tests\Feature\Http\Controllers\Project;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class ProjectShowTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_view_project_history(): void
    {
        $project = Project::factory()->create();

        $this->getJson('/projects/' . $project->id . '/history')
            ->assertUnauthorized();
    }

    #[Test]
    public function admin_can_view_project_history(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();

        $response = $this->getJson('/projects/' . $project->id . '/history');

        $response->assertOk();
    }

    #[Test]
    public function guest_cannot_duplicate_project(): void
    {
        $project = Project::factory()->create();

        $this->post('/projects/' . $project->id . '/duplicate')
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_duplicate_project(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create(['name' => 'Original']);

        $before = Project::query()->count();
        $response = $this->post('/projects/' . $project->id . '/duplicate');

        $response->assertRedirect();
        $this->assertGreaterThan($before, Project::query()->count());
        $this->assertDatabaseHas('projects', ['name' => '(Kopie) Original']);
    }

    #[Test]
    public function user_without_permission_gets_403_on_duplicate(): void
    {
        // Non-admin user with no project membership and no perms.
        $this->actingAs(User::factory()->create());
        $project = Project::factory()->create();
        // Attach an unrelated user to trigger the authorization branch
        $stranger = User::factory()->create();
        $project->users()->attach($stranger->id);

        $response = $this->postJson('/projects/' . $project->id . '/duplicate');

        $response->assertForbidden();
    }

    #[Test]
    public function admin_can_search_projects_without_group(): void
    {
        $this->actingAsAdmin();

        $response = $this->getJson(route('projects.search.single', ['query' => 'foo']));

        $response->assertOk();
        $this->assertIsArray($response->json());
    }

    #[Test]
    public function guest_cannot_search_projects_without_group(): void
    {
        $this->getJson(route('projects.search.single', ['query' => 'foo']))
            ->assertUnauthorized();
    }

    #[Test]
    public function admin_can_get_trashed(): void
    {
        $this->actingAsAdmin();

        $this->get(route('projects.trashed'))->assertOk();
    }

    #[Test]
    public function admin_can_view_artist_residency_settings_update_validates(): void
    {
        $this->actingAsAdmin();

        $response = $this->patch(route('project_settings.artist_residency.update'), []);

        $response->assertSessionHasErrors('breakfast_deduction_per_day');
    }

    #[Test]
    public function admin_can_update_artist_residency_settings(): void
    {
        $this->actingAsAdmin();

        $response = $this->patch(route('project_settings.artist_residency.update'), [
            'breakfast_deduction_per_day' => 5.5,
        ]);

        $response->assertRedirect();
    }
}
